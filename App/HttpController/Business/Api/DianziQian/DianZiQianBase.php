<?php

namespace App\HttpController\Business\Api\DianziQian;

use App\HttpController\Business\BusinessBase;

class DianZiQianBase extends BusinessBase
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