<?php

namespace App\HttpController\Business\Api\Export\Word;

use App\HttpController\Business\Api\Export\ExportBase;
use App\HttpController\Service\Report\ReportService;

class WordController extends ExportBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //生成一个简版报告
    function createEasy()
    {
        $reportNum=ReportService::getInstance()->createEasy('北京信任度科技有限公司');

        return $this->writeJson(200,'',$reportNum);
    }


}