<?php

namespace App\HttpController\Business\Provide\JinCai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;

class JinCaiController extends ProvideBase
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
    function obtainFpInfoNew(): bool
    {
        $isDetail = $this->getRequestData('isDetail');
        $nsrsbh = $this->getRequestData('nsrsbh');
        $startTime = $this->getRequestData('startTime');
        $endTime = $this->getRequestData('endTime');
        $pageNo = $this->getRequestData('pageNo');

        CommonService::getInstance()->log4PHP([$isDetail,  $nsrsbh,  $startTime,  $endTime,  $pageNo],'info','obtainFpInfoNew');
        $this->csp->add($this->cspKey, function () use ($isDetail,  $nsrsbh,  $startTime,  $endTime,  $pageNo) {

            return (new JinCaiShuKeService())->setCheckRespFlag(true)->obtainFpInfoNew( $isDetail,  $nsrsbh,  $startTime,  $endTime,  $pageNo);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
}