<?php

namespace App\HttpController\Business\Api\Common;

use App\HttpController\Business\BusinessBase;

class CommonBase extends BusinessBase
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