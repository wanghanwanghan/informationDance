<?php

namespace App\HttpController\Business\Api\LongXin;

use App\HttpController\Business\BusinessBase;

class LongXinBase extends BusinessBase
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