<?php

namespace App\HttpController\Business\Api\QianQi;

use App\HttpController\Business\BusinessBase;

class QianQiBase extends BusinessBase
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