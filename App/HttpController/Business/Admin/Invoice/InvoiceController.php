<?php

namespace App\HttpController\Business\Admin\Invoice;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
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

    function createList(): bool
    {
        $res = $this->getRequestData('zip_arr');

        CommonService::getInstance()->log4PHP($res);

        return $this->writeJson(200, null, $res);
    }


}