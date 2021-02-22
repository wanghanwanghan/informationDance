<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateSessionHandler;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\Pay\wx\wxPayService;
use Carbon\Carbon;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use Endroid\QrCode\QrCode;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        $uri = $this->request()->getUri()->__toString();

        $uri = explode('/', $uri);

        $num = count($uri);

        if ($uri[$num - 1] == 'userLogin' && $uri[$num - 2] == 'UserController') return true;

        if (empty($isLogin)) {
            //$this->writeJson(210, null, null, '用户未登录');
            //return false;
        }

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //用户登录
    function userLogin()
    {
        $username = $this->request()->getRequestParam('username') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        $iCode = $this->request()->getRequestParam('iCode') ?? '';

        if (empty($username) || empty($password) || empty($iCode)) return $this->writeJson(201, null, null, '登录信息错误');

        $redis = Redis::defer('redis');
        $redis->select(14);

        if (!$redis->sIsMember('imageVerifyCode', strtolower($iCode))) return $this->writeJson(201, null, null, '验证码错误');

        $userList = CreateConf::getInstance()->getConf('admin.user');

        $checkUsernamePassword = false;

        foreach ($userList as $one) {
            $info = explode(':', $one);

            if ($username == $info[0] && $password == $info[1]) {
                Session::getInstance()->set('isLogin', time());
                $checkUsernamePassword = true;
            }
        }

        return $checkUsernamePassword ? $this->writeJson(200, null, null, '登录成功') : $this->writeJson(201, null, null, '账号密码错误');
    }

    //添加用户
    function addUser()
    {
        $phone = $this->getRequestData('phone');
        $username = $this->getRequestData('username');
        $password = $this->getRequestData('password');
        $company = $this->getRequestData('company');
        $email = $this->getRequestData('email');
        $money = $this->getRequestData('money', 0);

        if (empty($phone)) return $this->writeJson(201, null, null, 'phone 不能是空');
        if (empty($username)) return $this->writeJson(201, null, null, 'username 不能是空');
        if (empty($password)) return $this->writeJson(201, null, null, 'password 不能是空');

        $info = User::create()->where('phone', $phone)->get();

        if (!empty($info)) return $this->writeJson(201, null, null, '手机号已注册');

        User::create()->data([
            'phone' => $phone,
            'username' => $username,
            'password' => $password,
            'company' => $company,
            'email' => $email,
        ])->save();

        Wallet::create()->data([
            'phone' => $phone,
            'money' => $money,
        ])->save();

        return $this->writeJson(200, null, null, '成功');
    }

    //用户列表
    function userList()
    {
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        try {
            $list = User::create()->alias('t1')
                ->join('information_dance_wallet as t2', 't2.phone = t1.phone')
                ->order('t1.created_at', 'desc')
                ->limit($this->exprOffset($page, $pageSize), $pageSize)
                ->all();

            $total = User::create()->alias('t1')
                ->join('information_dance_wallet as t2', 't2.phone = t1.phone')
                ->count('t1.id');

            $paging = ['page' => $page, 'pageSize' => $pageSize, 'total' => $total];

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        empty($list) ? $list = null : $list = obj2Arr($list);

        return $this->writeJson(200, $paging, $list, null);
    }

    //用户位置
    function userLocation()
    {
        try {
            $list = LngLat::create()->all();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        empty($list) ? $res = null : $res = obj2Arr($list);

        return $this->writeJson(200, null, $res, null);
    }

    //用户充值列表
    function userPurchaseList()
    {
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('page') ?? 10;
        $orderBy = $this->request()->getRequestParam('orderBy') ?? '';

        try {
            $list = PurchaseInfo::create()->alias('t1')
                ->join('information_dance_user as t2', 't2.phone = t1.phone')
                ->field('*')
                ->limit($this->exprOffset($page, $pageSize), $pageSize)
                ->all();

            $total = PurchaseInfo::create()->alias('t1')
                ->join('information_dance_user as t2', 't2.phone = t1.phone')
                ->count('t1.id');

            $paging = ['page' => $page, 'pageSize' => $pageSize, 'total' => $total];

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        empty($list) ? $list = null : $list = obj2Arr($list);

        //总充值金额
        try {
            $totalPurchase = PurchaseInfo::create()->where('orderStatus', '已支付')->sum('payMoney');
            $weekTotal = PurchaseInfo::create()
                ->where('orderStatus', '已支付')
                ->where('created_at', Carbon::now()->startOfWeek()->timestamp, '>')
                ->sum('payMoney');
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $info = [
            'totalPurchase' => $totalPurchase,
            'weekTotal' => $weekTotal,
        ];

        return $this->writeJson(200, $paging, ['info' => $info, 'list' => $list], null);
    }

    //用户充值
    function userPurchaseDo()
    {
        $jsCode = $this->request()->getRequestParam('jsCode') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $type = $this->request()->getRequestParam('type') ?? 1;
        $payConfType = $this->request()->getRequestParam('payConfType') ?? 'xd';
        $payWay = $this->request()->getRequestParam('payWay') ?? '';

        switch ($payWay) {
            case 'wx_scan':
                $payWayWord = '微信扫码';
                break;
            case 'ali_scan':
                $payWayWord = '支付宝扫码';
                break;
            default:
                $payWayWord = '';
        }

        if (empty($payWay)) return $this->writeJson(201, null, null, '支付方式错误');

        try {
            $list = PurchaseInfo::create()->where('id', $type)->get();
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
            'payWay' => $payWayWord,
        ];

        try {
            $w = 123;
            // PurchaseInfo::create()->data($insert)->save();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        switch ($payWay) {
            case 'wx_scan':
                $payObj = (new wxPayService())->setPayConfType($payConfType)->scan();
                break;
            case 'ali_scan':
                $payObj = '支付宝扫码';
                break;
            default:
                $payObj = '';
        }

        $qrCode = new QrCode($payObj);

        $this->response()->withHeader('Content-Type', $qrCode->getContentType());


        //return $this->writeJson(200, null, ['orderId' => $orderId, 'payObj' => $payObj], '生成订单成功');
        return $this->response()->write($payObj);
    }
}