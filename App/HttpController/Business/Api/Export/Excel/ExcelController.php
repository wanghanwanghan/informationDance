<?php

namespace App\HttpController\Business\Api\Export\Excel;

use App\HttpController\Business\Api\Export\ExportBase;

class ExcelController extends ExportBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function test()
    {



    }


}