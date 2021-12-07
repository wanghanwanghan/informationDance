<?php

namespace App\HttpController\Business\Provide\YiZhangTong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\YiZhangTong\YiZhangTongService;
use wanghanwanghan\someUtils\control;

class YiZhangTongController extends ProvideBase
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

    //产品列表接口
    function getProductList(): bool
    {
        $this->csp->add($this->cspKey, function () {
            return (new YiZhangTongService())->setCheckRespFlag(true)->getProductList();
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //静默注册/登陆功能接口
    function getLogin(): bool
    {
        $post_data = [
            'requestId' => $this->getRequestData('requestId', control::getUuid()),
            'silentLoginFlag' => $this->getRequestData('silentLoginFlag', '0'),
            'userName' => $this->getRequestData('userName'),
            'certificateNum' => $this->getRequestData('certificateNum'),
            'userPhone' => $this->getRequestData('userPhone'),
            'companyName' => $this->getRequestData('companyName'),
            'productCode' => $this->getRequestData('productCode'),
            'UMCode' => $this->getRequestData('UMCode'),
            'marketPersonnelCode' => $this->getRequestData('marketPersonnelCode'),
        ];

        $this->csp->add($this->cspKey, function () use ($post_data) {
            return (new YiZhangTongService())->setCheckRespFlag(true)->getLogin($post_data);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //订单列表查询
    function getOrderList(): bool
    {
        $post_data = [
            'loanUserName' => $this->getRequestData('loanUserName'),
            'loanCorpName' => $this->getRequestData('loanCorpName'),
            'loanOrderCode' => $this->getRequestData('loanOrderCode'),
            'caUser' => $this->getRequestData('caUser'),
            'productCode' => $this->getRequestData('productCode'),
            'startDate' => $this->getRequestData('startDate'),
            'endDate' => $this->getRequestData('endDate'),
            'currentPage' => $this->getRequestData('currentPage'),
            'pageSize' => $this->getRequestData('pageSize'),
        ];

        $this->csp->add($this->cspKey, function () use ($post_data) {
            return (new YiZhangTongService())->setCheckRespFlag(true)->getOrderList($post_data);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



