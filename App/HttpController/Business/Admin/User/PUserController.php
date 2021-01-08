<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\Session\Session;
use wanghanwanghan\someUtils\control;

class PUserController extends UserBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getUserList()
    {
        $userInfo = RequestUserInfo::create()->all();

        return $this->writeJson(200, null, $userInfo);
    }

    //添加用户
    function addUser()
    {
        $username = $this->getRequestData('username');
        $money = $this->getRequestData('money');

        if (empty($username) || empty($money)) return $this->writeJson(201);

        $check = RequestUserInfo::create()->where('username', $username)->get();

        if (empty($check)) {
            $appId = strtoupper(control::getUuid());
            $appSecret = substr(strtoupper(control::getUuid()), 5, 20);
            RequestUserInfo::create()->data([
                'username' => $username,
                'appId' => $appId,
                'appSecret' => $appSecret,
                'money' => $money,
            ])->save();
        }

        return $this->writeJson(200);
    }

    //编辑用户
    function editUser()
    {
        $id = $this->getRequestData('id');
        $money = $this->getRequestData('money');

        if (empty($id) || empty($money)) return $this->writeJson(201);

        $userInfo = RequestUserInfo::create()->where('id', $id)->get();

        if (empty($userInfo)) return $this->writeJson(201);

        $userInfo->update([
            'money' => QueryBuilder::inc($money)
        ]);

        return $this->writeJson(200);
    }

    //用户都有哪些api
    function getUserApi()
    {
        $id = $this->getRequestData('id');

        $res = RequestUserApiRelationship::create()->alias('t1')
            ->join('information_dance_request_api_info as t2', 't1.apiId = t2.id', 'left')
            ->field([
                't1.apiId',
                't1.price AS custPrice',
                't2.*',
            ])->where('t1.userId', $id)->all();

        return $this->writeJson(200, null, $res);
    }

    //editUserApi


}