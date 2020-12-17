<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Provide\RequestUserInfo;

class QiChaChaController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    private function getCsp()
    {
        return CspService::getInstance()->create();
    }

    function getTest()
    {
        $this->responseCode = 200;
        $this->responseData = [
            'wanghan'=>123,
            'hkf'=>321,
        ];
    }





}