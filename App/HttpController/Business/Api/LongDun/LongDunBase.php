<?php

namespace App\HttpController\Business\Api\LongDun;

use App\HttpController\Business\BusinessBase;

class LongDunBase extends BusinessBase
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