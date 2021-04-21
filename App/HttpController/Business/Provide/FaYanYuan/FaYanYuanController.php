<?php

namespace App\HttpController\Business\Provide\FaYanYuan;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;

class FaYanYuanController extends ProvideBase
{
    public $list;

    function onRequest(?string $action): ?bool
    {
        $this->list = CreateConf::getInstance()->getConf('fayanyuan.list');
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
        $url = $this->list . 'entout/portrait/org';

        $postData = [
            'name' => $this->getRequestData('entName'),
            'id' => ''
        ];

        $this->csp->add($this->cspKey, function () use ($url, $postData) {
            return (new FaYanYuanService())->setCheckRespFlag(true)->entoutOrg($url, $postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}



