<?php

namespace App\HttpController\Business\Api\FaYanYuan;

use App\HttpController\Business\BusinessBase;

class FaYanYuanBase extends BusinessBase
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