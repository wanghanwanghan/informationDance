<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Business\BusinessBase;

class UserBase extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}