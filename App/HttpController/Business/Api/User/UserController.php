<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\SupervisorPhoneLimit;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\OneSaid\OneSaidService;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Pay\wx\wxPayService;
use App\HttpController\Service\User\UserService;
use Carbon\Carbon;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{
    private $connDB;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        //DB链接名称
        $this->connDB = CreateConf::getInstance()->getConf('env.mysqlDatabase');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //注册
    function reg()
    {
        $company = $this->request()->getRequestParam('company') ?? '';
        $username = $this->request()->getRequestParam('username') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $email = $this->request()->getRequestParam('email') ?? '';

        $password = $this->request()->getRequestParam('password') ?? control::randNum(6);
        $avatar = $this->request()->getRequestParam('avatar') ?? '';

        $vCode = $this->request()->getRequestParam('vCode') ?? '';

        if (empty($phone) || empty($vCode)) return $this->writeJson(201, null, null, '手机号或验证码不能是空');

        if (!is_numeric($phone) || !is_numeric($vCode)) return $this->writeJson(201, null, null, '手机号或验证码必须是数字');

        if (strlen($phone) !== 11) return $this->writeJson(201, null, null, '手机号错误');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $vCodeInRedis = $redis->get($phone . 'reg');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        try {
            $res = User::create()->where('phone', $phone)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //已经注册过了
        if ($res) return $this->writeJson(201, null, null, '手机号已经注册过了');

        try {
            $token = UserService::getInstance()->createAccessToken($phone, $password);
            $insert = [
                'username' => $username,
                'password' => $password,
                'phone' => $phone,
                'email' => $email,
                'avatar' => $avatar,
                'token' => $token,
                'company' => $company
            ];
            User::create()->data($insert, false)->save();
            Wallet::create()->data(['phone' => $phone], false)->save();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $insert, '注册成功');
    }

    //登录
    function login()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';

        if (empty($phone) || empty($vCode)) return $this->writeJson(201, null, null, '手机号或验证码不能是空');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $vCodeInRedis = $redis->get($phone . 'login');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (empty($userInfo)) return $this->writeJson(201, null, null, '手机号不存在');

        $newToken = UserService::getInstance()->createAccessToken($userInfo->phone, $userInfo->password);

        try {
            $user = User::create()->get($userInfo->id);
            $user->update(['token' => $newToken]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $userInfo->newToken = $newToken;

        return $this->writeJson(200, null, $userInfo, '登录成功');
    }

    //获取充值商品列表
    function purchaseGoods()
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try
        {
            $info = PurchaseList::create()->limit($this->exprOffset($page,$pageSize),(int)$pageSize)->all();

            //拿到数据
            $info = jsonDecode(jsonEncode($info));

            //数据的总记录条数
            $total = PurchaseInfo::create()->count();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        !empty($info) ?: $info = null;

        return $this->writeJson(200,['page'=>$page,'pageSize'=>$pageSize,'total'=>$total],$info,'查询成功');
    }

    //获取用户充值详情列表
    function purchaseList()
    {
        $phone = $this->request()->getRequestParam('phone');
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try
        {
            $info = PurchaseInfo::create()->where('phone',$phone)->order('updated_at','desc')
                ->limit($this->exprOffset($page,$pageSize),(int)$pageSize)->all();

            //拿到数据
            $info = jsonDecode(jsonEncode($info));

            //数据的总记录条数
            $total = PurchaseInfo::create()->where('phone',$phone)->count();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        !empty($info) ?: $info = null;

        return $this->writeJson(200,['page'=>$page,'pageSize'=>$pageSize,'total'=>$total],$info,'查询成功');
    }

    //充值
    function purchaseDo()
    {
        $jsCode = $this->request()->getRequestParam('jsCode') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 1;

        try {
            $list = PurchaseList::create()->where('id', $type)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //后三位备用
        $orderId = Carbon::now()->format('YmdHis') . control::randNum(2) . str_pad(0, 3, 0, STR_PAD_LEFT);

        //创建订单
        $insert = [
            'phone' => $phone,
            'orderId' => $orderId,
            'orderStatus' => '待支付',
            'purchaseType' => $type,
            'payMoney' => $list->money,
        ];

        try {
            PurchaseInfo::create()->data($insert, false)->save();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //创建小程序支付对象
        $payObj = (new wxPayService())->miniAppPay($jsCode, $orderId, $list->money, $list->name . ' - ' . $list->desc);

        return $this->writeJson(200, null, ['orderId' => $orderId, 'payObj' => $payObj], '生成订单成功');
    }

    //发布一句话
    function createOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $oneSaid = $this->request()->getRequestParam('oneSaid') ?? '';
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        $oneSaid = trim($oneSaid);

        if (empty($oneSaid) || mb_strlen($oneSaid) > 255) return $this->writeJson(201, null, null, 'oneSaid错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->createOneSaid($phone, $oneSaid, $moduleId, $entName), '发布成功');
    }

    //修改一句话
    function editOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $oneSaid = $this->request()->getRequestParam('oneSaid') ?? '';
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        $oneSaid = trim($oneSaid);

        if (empty($oneSaid) || mb_strlen($oneSaid) > 255) return $this->writeJson(201, null, null, 'oneSaid错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->createOneSaid($phone, $oneSaid, $moduleId, $entName), '修改成功');
    }

    //获取用户发布一句话
    function getOneSaid()
    {
        $phone = $this->request()->getRequestParam('phone');
        $moduleId = $this->request()->getRequestParam('moduleId') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $entName = trim($entName);

        if (empty($entName)) return $this->writeJson(201, null, null, 'entName错误');

        if (!is_numeric($moduleId)) return $this->writeJson(201, null, null, 'moduleId错误');

        return $this->writeJson(200, null, OneSaidService::getInstance()->getOneSaid($phone, $moduleId, $entName, false), '获取成功');
    }

    //创建风险监控
    function createSupervisor()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $charge = ChargeService::getInstance()->Supervisor($this->request(), 50);

        if ($charge['code'] != 200) return $this->writeJson($charge['code'], null, null, $charge['msg']);

        try {

            //先看添加没添加过
            $data = SupervisorPhoneEntName::create()->where('phone', $phone)->where('entName', $entName)->get();

            if (empty($data)) {

                //没添加过
                $data = [
                    'phone' => $phone,
                    'entName' => $entName,
                    'status' => 1,
                    'expireTime' => time() + CreateConf::getInstance()->getConf('supervisor.chargeLimit') * 86400,
                ];

                SupervisorPhoneEntName::create()->data($data, false)->save();

            } else {

                //添加过了
                $data->update([
                    'status' => 1,
                    'expireTime' => time() + CreateConf::getInstance()->getConf('supervisor.chargeLimit') * 86400
                ]);
                $data = $data->toArray();
            }

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $data, '添加成功');
    }

    //修改风险阈值
    function supervisorLimit()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $sf = $this->request()->getRequestParam('sf') ?? 1;
        $gs = $this->request()->getRequestParam('gs') ?? 1;
        $gl = $this->request()->getRequestParam('gl') ?? 1;
        $jy = $this->request()->getRequestParam('jy') ?? 1;
        $entName = trim($entName);

        if (empty($phone) || empty($entName)) return $this->writeJson(201, null, null, '手机号和企业名不能是空');

        try {
            $info = SupervisorPhoneLimit::create()->where('phone', $phone)->where('entName', $entName)->get();

            $data = [
                'phone' => $phone,
                'entName' => $entName,
                'sf' => $sf,
                'gs' => $gs,
                'gl' => $gl,
                'jy' => $jy,
            ];

            if (empty($info)) {
                SupervisorPhoneLimit::create()->data($data)->save();
            } else {
                $info->update($data);
            }

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->writeJson(200, null, $data, '成功');
    }

    //获取报告列表
    function getReportList()
    {
        $phone = $this->request()->getRequestParam('phone');
        $type = $this->request()->getRequestParam('type') ?? 255;
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        try
        {
            $info = ReportInfo::create()->where('phone',$phone)->order('updated_at','desc')
                ->limit($this->exprOffset($page,$pageSize),(int)$pageSize);

            if (is_numeric($type) && $type != 255) $info->where('type',$type);

            //拿到数据
            $info = jsonDecode(jsonEncode($info->all()));

            $total = ReportInfo::create()->where('phone',$phone);

            if (is_numeric($type) && $type != 255) $total->where('type',$type);

            //数据的总记录条数
            $total = $total->count();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        !empty($info) ?: $info = null;

        return $this->writeJson(200,['page'=>$page,'pageSize'=>$pageSize,'total'=>$total],$info,'查询成功');
    }

    //上传授权书后的确认按钮
    function createAuthBook()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $path = $this->request()->getRequestParam('path') ?? '';

        if (empty($entName) || empty($path)) return $this->writeJson(201, null, null, '授权书path和企业名不能是空');

        $filename = explode(DIRECTORY_SEPARATOR,$path);

        $filename = end($filename);

        try
        {
            AuthBook::create()->data([
                'phone'=>$phone,
                'entName'=>$entName,
                'name'=>$filename,
                'status'=>1,
                'remark'=>'',
            ])->save();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        return $this->writeJson(200,null,null,'提交成功');
    }

}