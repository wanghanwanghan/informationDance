<?php

namespace App\HttpController\Business\Admin\Api;

use App\HttpController\Business\BusinessBase;

class ApiBase extends BusinessBase
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