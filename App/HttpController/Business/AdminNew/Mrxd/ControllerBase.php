<?php

namespace App\HttpController\Business\AdminNew\Mrxd;

use App\HttpController\Index;

class ControllerBase extends Index
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