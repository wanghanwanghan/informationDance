<?php

namespace App\HttpController\Business\Provide\QianQi;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\QianQi\QianQiService;

class QianQiController extends ProvideBase
{
    public $cspTimeout = 5;

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
        if (empty($res)) {
            //超时了
            $res = [];
            $this->responseCode = 500;
            $this->responseData = $res;
            $this->spendMoney = 0;
            $msg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responseData = $res[$this->cspKey]['result'];
            $msg = $res[$this->cspKey]['msg'];
        }

        $info = [
            'requestUrl' => $this->requestUrl,
            'requestId' => $this->requestId,
            'requestData' => $this->requestData,
        ];

        return $this->writeJson($this->responseCode, null, $this->responseData, $msg, $info);
    }

    function getThreeYearsData()
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new QianQiService())->setCheckRespFlag(true)->getThreeYears($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



