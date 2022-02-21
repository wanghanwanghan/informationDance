<?php

namespace App\HttpController\Business\AdminNew\Mrxd\Api;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;

class ApiController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }


}