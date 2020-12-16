<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Provide\RequestRecode;

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
        RequestRecode::create()->addSuffix(date('Y'))->data([
            'userId' => 1,
            'ProvideApiId' => 1,
            'requestId' => 1,
            'requestUrl' => 1,
            'requestData' => jsonEncode(['page'=>1,'pageSize'=>10]),
            'responseCode' => 1,
            'responseData' => jsonEncode(['page'=>1,'pageSize'=>10]),
            'spendTime' => 1,
            'spendMoney' => 1,
        ])->save();
    }





}