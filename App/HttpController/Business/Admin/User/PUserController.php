<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\CreateSessionHandler;
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

    function addUser()
    {
        $username = $this->getRequestData('username');
        $money = $this->getRequestData('money');

        $check = RequestUserInfo::create()->where('username', $username)->get();

        if (empty($check))
        {
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


}