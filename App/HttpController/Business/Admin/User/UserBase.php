<?php

namespace App\HttpController\Business\Admin\User;

use App\HttpController\Business\BusinessBase;

class UserBase extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}