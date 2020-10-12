<?php

namespace App\HttpController\Business\Api\YuanSu;

use App\HttpController\Business\BusinessBase;

class YuanSuBase extends BusinessBase
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