<?php

namespace App\HttpController\Business\AdminNew\Mrxd\User;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;

class UserController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function reg(): bool
    {
        $company = $this->request()->getRequestParam('company') ?? '';
        $username = $this->request()->getRequestParam('username') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $email = $this->request()->getRequestParam('email') ?? 'test@test.com';
        $pidPhone = $this->request()->getRequestParam('pidPhone') ?? 0;//注册裂变

        return $this->writeJson(200, null, '', '注册成功');
    }

}