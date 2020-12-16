<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;

class QiChaChaController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);
        return true;
    }

    function afterAction(?string $actionName): void
    {
        $this->responseTime = time();
        parent::afterAction($actionName);
    }

    function getTest()
    {
        CreateTableService::getInstance()->information_dance_request_recode();
    }





}