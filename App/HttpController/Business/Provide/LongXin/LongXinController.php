<?php

namespace App\HttpController\Business\Provide\LongXin;

use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;

class LongXinController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验返回值，并给客户计费
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

    //近n年的财务数据，不需要授权
    function getFinance()
    {
        $entName = $this->getRequestData('entName');
        $code = $this->getRequestData('code');

        $postData = [
            'entName' => $entName,
            'code' => $code,
            'beginYear' => date('Y') - 2,
            'dataCount' => 3,//取最近几年的
        ];

        $res = $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData, false);
        });

        return $this->checkResponse($res);
    }

}