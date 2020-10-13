<?php

namespace App\HttpController\Business\Api\Export\Word;

use App\HttpController\Business\Api\Export\ExportBase;
use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\Report\ReportService;
use wanghanwanghan\someUtils\control;

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
        $reportNum = time() . '_' . control::getUuid(3);

        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $charge = ChargeService::getInstance()->EasyReport($this->request(), 200, $reportNum);

        if ($charge['code'] != 200) {
            $code = $charge['code'];
            $paging = $res = null;
            $msg = $charge['msg'];
        } else {
            $code = 200;
            $paging = null;
            $res = ReportService::getInstance()->createEasy($entName, $reportNum, $phone);
            $msg = '简版报告报告生成中';
        }

        return $this->writeJson($code, $paging, $res, $msg);
    }


}