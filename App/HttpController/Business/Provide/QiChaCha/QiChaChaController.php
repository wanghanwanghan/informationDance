<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Provide\RequestUserInfo;
use EasySwoole\Mysqli\QueryBuilder;

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

    function getTest()
    {
        $csp = CspService::getInstance()->create();

        $csp->add('wh',function () {
            return [
                'wanghan'=>123,
                'hkf'=>321,
            ];
        });

        $res = CspService::getInstance()->exec($csp);

        $this->responseCode = 200;
        $this->responseData = $res['wh'];

        $res=RequestUserInfo::create()->get($this->userId);

        $res->update([
            'money' => QueryBuilder::dec($this->spendMoney)
        ]);
    }





}