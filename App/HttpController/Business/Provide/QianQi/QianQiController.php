<?php

namespace App\HttpController\Business\Provide\QianQi;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;

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
        $this->responseCode = 200;
        $this->responseData = $res;

        return $this->writeJson(200, null, $res);
    }

    function getThreeYearsData()
    {
        $this->csp->add($this->cspKey, function () {
            return 'wanghan123';
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



