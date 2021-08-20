<?php

namespace App\HttpController\Business\Admin\Invoice;

use App\HttpController\Models\Api\AntAuthList;
use wanghanwanghan\someUtils\control;

class InvoiceController extends InvoiceBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getList(): bool
    {
        $res = AntAuthList::create()->all();

        return $this->writeJson(200, null, $res);
    }


}