<?php

namespace App\HttpController\Business\Api\XinDong;

use App\HttpController\Business\BusinessBase;

class XinDongBase extends BusinessBase
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