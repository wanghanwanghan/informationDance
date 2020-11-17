<?php

namespace App\HttpController\Business\Api\ZhongWang;

use App\HttpController\Business\BusinessBase;

class ZhongWangBase extends BusinessBase
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