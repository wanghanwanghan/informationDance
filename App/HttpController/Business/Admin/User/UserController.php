<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Api\LngLat;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;

class UserController extends UserBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        $uri = $this->request()->getUri()->__toString();

        $uri = explode('/',$uri);

        $num = count($uri);

        if ($uri[$num-1] == 'userLogin' && $uri[$num-2] == 'UserController') return true;

        if (empty($isLogin)) {
            $this->writeJson(210, null, null, '用户未登录');
            return false;
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

        foreach ($userList as $one)
        {
            $info = explode(':',$one);

            if ($username == $info[0] && $password == $info[1])
            {
                Session::getInstance()->set('isLogin',time());
                $checkUsernamePassword = true;
            }
        }

        return $checkUsernamePassword ? $this->writeJson(200,null,null,'登录成功') : $this->writeJson(201,null,null,'账号密码错误');
    }

    //用户列表
    function userList()
    {
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('page') ?? 10;

        try {
            $list = User::create()->alias('t1')
                ->join('information_dance_wallet as t2', 't2.phone = t1.phone')
                ->limit($this->exprOffset($page, $pageSize), $pageSize)
                ->all();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        empty($list) ? $res = null : $res = obj2Arr($list);

        return $this->writeJson(200, null, $res, null);
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


}