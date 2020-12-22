<?php

namespace App\HttpController\Business\Provide\QianQi;

use App\HttpController\Business\Provide\ProvideBase;

class QianQiController extends ProvideBase
{
    public $cspTimeOut = 5;

    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getThreeYearsData()
    {
        $res = $this->csp->add($this->cspKey, function () {
            return 'wanghan123';
        });

        return $this->writeJson(200,null,$res);
    }





}