<?php

namespace App\HttpController\Business\Provide\BaiXiang;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\BaiXiang\BaiXiangService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class BaiXiangController extends ProvideBase
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

    //药典通 企业详情
    function getDptEnterpriseMedicineDetailList(): bool
    {
        $entName = $this->getRequestData('entName');

        $postData = [
            'entname' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new BaiXiangService())
                ->setCheckRespFlag(true)
                ->getDptEnterpriseMedicineDetailList($postData['entname']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //药典通 药品详情
    function getDptDrugDetail(): bool
    {
        $drugCode = $this->getRequestData('drugCode');

        $postData = [
            'drugcode' => $drugCode,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new BaiXiangService())
                ->setCheckRespFlag(true)
                ->getDptDrugDetail($postData['drugcode']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }





}



