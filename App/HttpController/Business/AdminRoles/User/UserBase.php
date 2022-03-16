<?php

namespace App\HttpController\Business\AdminRoles\User;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Session\Session;

class UserBase extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}