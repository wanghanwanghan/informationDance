<?php

namespace App\HttpController\Business\Admin\Statistics;

use App\HttpController\Business\BusinessBase;

class StatisticsBase extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}