<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;

class QiChaChaController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);
        CommonService::getInstance()->log4PHP('qichacha onRequest');
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
        CommonService::getInstance()->log4PHP('qichacha afterAction');
    }

    function getTest()
    {
        CommonService::getInstance()->log4PHP(__FUNCTION__);
    }





}