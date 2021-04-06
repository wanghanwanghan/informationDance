<?php

namespace App\HttpController\Business\Api\GuoPiao;

use App\HttpController\Business\BusinessBase;

class GuoPiaoBase extends BusinessBase
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