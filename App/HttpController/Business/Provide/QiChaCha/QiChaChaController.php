<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;

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
        $cspKey = control::getUuid();

        $csp = CspService::getInstance()->create();

        $csp->add($csp,function () {
            \co::sleep(3);
            return [
                'wanghan'=>123,
                'hkf'=>321,
            ];
        });

        $res = CspService::getInstance()->exec($csp,1);

        if (empty($res))
        {
            $this->responseData = [];
            $this->responseCode = 201;
        }
    }





}