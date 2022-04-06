<?php

namespace App\HttpController\Business\Provide\GuangZhouYinLian;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\GuangZhouYinLian\GuangZhouYinLianService;

class GuangZhouYinLianController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function queryVehicleCount(): bool
    {
        $postData = [];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new GuangZhouYinLianService())->setCheckRespFlag(true)->queryVehicleCount($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);

    }

    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode   = $res['code'] ?? 500;
            $this->responsePaging = $res['paging'] ?? null;
            $this->responseData   = $res['response'] ?? null;
            $this->spendMoney     = 0;
            $this->responseMsg    = $res['msg'] ?? '请求超时';
        } else {
            $this->responseCode   = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData   = $res[$this->cspKey]['response'];
            $this->responseMsg    = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    function queryInancialBank(): bool
    {
        $name      = $this->getRequestData('name');
        $userNo    = $this->getRequestData('userNo');
        $certType  = $this->getRequestData('certType');
        $certNo    = $this->getRequestData('certNo');
        $vin       = $this->getRequestData('vin');
        $licenseNo = $this->getRequestData('licenseNo');
        $bizFunc   = $this->getRequestData('bizFunc');
        $postData  = [
            'name'      => $name,
            'userNo'    => $userNo,
            'certType'  => $certType,
            'certNo'    => $certNo,
            'vin'       => $vin,
            'licenseNo' => $licenseNo,
            'bizFunc'   => $bizFunc
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new GuangZhouYinLianService())->setCheckRespFlag(true)->queryInancialBank($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);

    }
}