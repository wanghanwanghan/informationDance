<?php

namespace App\HttpController\Business\Provide\ShenZhouYunHe;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ShenZhouYunHe\ShenZhouYunHeService;

class ShenZhouYunHeController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    function invoices(): bool
    {
        $taxNum = $this->getRequestData('taxNum');
        $billingDateStart = $this->getRequestData('billingDateStart');
        $billingDateEnd = $this->getRequestData('billingDateEnd');
        $startDate = $this->getRequestData('startDate');
        $endDate = $this->getRequestData('endDate');
        $invoiceType = $this->getRequestData('invoiceType');
        $sjlx = $this->getRequestData('sjlx');
        $pageNum = $this->getRequestData('pageNum')??1;
        $pageSize = $this->getRequestData('pageSize')??100;
        $postData = [
            'taxNum' => $taxNum,
            'billingDateStart' => $billingDateStart,
            'billingDateEnd' => $billingDateEnd,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'invoiceType' => $invoiceType,
            'sjlx' => $sjlx,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        ];
        dingAlarm('invoices',['$postData'=>json_encode($postData)]);
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new ShenZhouYunHeService())->setCheckRespFlag(true)->invoices($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function collection(): bool
    {
        $taxNum = $this->getRequestData('taxNum');
        $reportPeriod = $this->getRequestData('reportPeriod');
        $pageNum = $this->getRequestData('pageNum')??1;
        $pageSize = $this->getRequestData('pageSize')??100;
        $postData = [
            'taxNum' => $taxNum,
            'reportPeriod' => $reportPeriod,
            'pageNum' => $pageNum,
            'pageSize' => $pageSize
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new ShenZhouYunHeService())->setCheckRespFlag(true)->collection($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
}