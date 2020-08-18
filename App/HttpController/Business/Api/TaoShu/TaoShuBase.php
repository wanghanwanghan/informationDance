<?php

namespace App\HttpController\Business\Api\TaoShu;

use App\HttpController\Business\BusinessBase;

class TaoShuBase extends BusinessBase
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