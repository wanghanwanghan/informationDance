<?php

namespace App\HttpController\Business\Api\HuoYan;

use App\HttpController\Index;

class HuoYanBase extends Index
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