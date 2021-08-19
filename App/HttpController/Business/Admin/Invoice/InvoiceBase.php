<?php

namespace App\HttpController\Business\Admin\Invoice;

use App\HttpController\Business\BusinessBase;

class InvoiceBase extends BusinessBase
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