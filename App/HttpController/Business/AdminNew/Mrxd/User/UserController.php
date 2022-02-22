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
        return $this->writeJson();
    }

    //登录
    //修改密码   用户冻结， 新增用户（管理员）

}