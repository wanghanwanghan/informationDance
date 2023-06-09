<?php

namespace App\HttpController\Business\Provide\DaTong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\TaoShu\TaoShuTwoService;

class DaTongController extends ProvideBase
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

    function getChattelMortgageInfo()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $pageNo = $this->request()->getRequestParam('pageNo') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $postData = [
            'entName' => $entName,
            'pageNo' => $pageNo,
            'pageSize' => $pageSize,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getChattelMortgageInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}