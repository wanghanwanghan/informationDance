<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Session\Session;

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

        return $this->writeJson(200,null,$userInfo);
    }






}