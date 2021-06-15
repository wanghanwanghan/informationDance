<?php

namespace App\HttpController\Business\Provide\FaYanYuan;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class FaYanYuanController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res)
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

    //公开模型 企业
    function entoutOrg()
    {
        $postData = [
            'name' => $this->getRequestData('entName'),
            'id' => ''
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())->setCheckRespFlag(true)->entoutOrg($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //公开模型 个人
    function entoutPeople()
    {
        $postData = [
            'name' => $this->getRequestData('entName'),
            'id' => $this->getRequestData('id')
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new FaYanYuanService())->setCheckRespFlag(true)->entoutPeople($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



