<?php

namespace App\HttpController\Business\Provide\WhTest;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\TaoShu\TaoShuService;

class WhTestController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getBranchInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        CommonService::getInstance()->log4PHP($postData, 'info', 'wanghantest.log');

        $entName = $this->getRequestData('entName', '');
        $pageNo = $this->getRequestData('$pageNo', 1);
        $pageSize = $this->getRequestData('$pageSize', 10);

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        CommonService::getInstance()->log4PHP($postData, 'info', 'wanghantest.log');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getBranchInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        CommonService::getInstance()->log4PHP($res, 'info', 'wanghantest.log');

    }

}