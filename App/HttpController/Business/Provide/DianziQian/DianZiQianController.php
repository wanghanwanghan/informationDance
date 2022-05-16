<?php

namespace App\HttpController\Business\Provide\DianziQian;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;

class DianZiQianController extends ProvideBase
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

    function getAuthFile(): bool
    {
        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('socialCredit');
        $legalPerson = $this->getRequestData('legalPerson');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');

        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
        ];
        CommonService::getInstance()->log4PHP([$postData],'info','getAuthFile');

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new DianZiQianService())->setCheckRespFlag(true)->getAuthFile($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    public function getUrl(){
        $this->csp->add($this->cspKey, function () {
            return (new DianZiQianService())->setCheckRespFlag(true)->getUrl();
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
}