<?php

namespace App\HttpController\Business\Api\DianziQian;

use App\Csp\Service\CspService;
use App\HttpController\Business\Api\DianziQian\DianZiQianBase;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;

class DianZiQianController extends DianZiQianBase
{

    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
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
        $file = $this->getRequestData('file');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'regAddress' => $regAddress,
            'file' => $file
        ];
        $res = (new DianZiQianService())->getAuthFile($postData);
        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }

    public function getCarAuthFile(){


        $entName = $this->getRequestData('entName');
        $socialCredit = $this->getRequestData('entCode');
        $legalPerson = $this->getRequestData('operName');
        $idCard = $this->getRequestData('idCard');
        $phone = $this->getRequestData('phone');
        $city = $this->getRequestData('city');
        $regAddress = $this->getRequestData('regAddress');
        $vin = $this->getRequestData('vinStr');
        $postData = [
            'entName' => $entName,
            'socialCredit' => $socialCredit,
            'legalPerson' => $legalPerson,
            'idCard' => $idCard,
            'phone' => $phone,
            'city' => $city,
            'vin' => $vin
        ];

        $res = (new DianZiQianService())->getCarAuthFile($postData);
        CommonService::getInstance()->log4PHP($res,'info','getCarAuthFile');
        return $this->writeJson($res['code'], null, $res['data'], $res['description']);

    }

    public function doTemporaryAction(){
        $res = (new DianZiQianService())->doTemporaryAction();
        return $this->writeJson($res['code'], null, $res['data'], $res['description']);
    }
}