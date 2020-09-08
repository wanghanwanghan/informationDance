<?php

namespace App\HttpController\Business\Api\Order;

use App\HttpController\Business\BusinessBase;

class OrderBase extends BusinessBase
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