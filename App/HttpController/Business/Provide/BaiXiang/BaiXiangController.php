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

        preg_match('/([\x81-\xfe][\x40-\xfe])/', $entName) ?
            $postData = ['entName' => $entName, 'code' => '',] :
            $postData = ['entName' => '', 'code' => $entName,];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new BaiXiangService())
                ->setCheckRespFlag(true)
                ->getDptEnterpriseMedicineDetailList($postData['entName'], $postData['code'], '');
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

    //药典通 医疗机构详情
    function getDptHospitalDetail(): bool
    {
        $entName = $this->getRequestData('entName');

        preg_match('/([\x81-\xfe][\x40-\xfe])/', $entName) ?
            $postData = ['entName' => $entName, 'code' => '',] :
            $postData = ['entName' => '', 'code' => $entName,];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new BaiXiangService())
                ->setCheckRespFlag(true)
                ->getDptHospitalDetail($postData['entName'], $postData['code']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //药典通 医疗器械详情
    function getDptInstrumentDetail(): bool
    {
        $codetype = $this->getRequestData('codetype');//注册证编号/备案号类型 0:注册证号或备案号 1:注册证编号 2:备案号
        $instrumentcode = $this->getRequestData('instrumentcode');//注册证编号/备案号

        $postData = [
            'codetype' => $codetype,
            'instrumentcode' => $instrumentcode,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new BaiXiangService())
                ->setCheckRespFlag(true)
                ->getDptInstrumentDetail($postData['codetype'], $postData['instrumentcode']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



