<?php

namespace App\HttpController\Business\Admin\Finance;

use App\HttpController\Business\BusinessBase;

class FinanceBase extends BusinessBase
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