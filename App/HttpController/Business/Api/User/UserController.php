<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
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
            return $this->writeErr($e, 'orm');
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
            return $this->writeErr($e, 'orm');
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
            return $this->writeErr($e, 'orm');
        }

        if (empty($userInfo)) return $this->writeJson(201, null, null, '手机号不存在');

        $newToken = UserService::getInstance()->createAccessToken($userInfo->phone, $userInfo->password);

        try {
            $user = User::create()->get($userInfo->id);
            $user->update(['token' => $newToken]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, 'orm');
        }

        $userInfo->newToken = $newToken;

        return $this->writeJson(200, null, $userInfo, '登录成功');
    }

    //获取商品列表
    function purchaseList()
    {
        try {
            $list = PurchaseList::create()->all();
        } catch (\Throwable $e) {
            return $this->writeErr($e, 'orm');
        }

        empty($list) ? $list = null : $list = json_decode(json_encode($list));

        return $this->writeJson(200, null, $list, '成功');
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

    //创建风险监控
    function createSupervisor()
    {
        $phone = $this->request()->getRequestParam('phone');
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $charge = ChargeService::getInstance()->Supervisor($this->request(),50);

        CreateTableService::getInstance()->information_dance_supervisor_entName_info();
        CreateTableService::getInstance()->information_dance_supervisor_uid_entName();





    }




}