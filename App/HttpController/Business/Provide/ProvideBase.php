<?php

namespace App\HttpController\Business\Provide;

use App\HttpController\Index;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class ProvideBase extends Index
{
    public $qccListUrl;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);
        $this->qccListUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        CommonService::getInstance()->log4PHP('ProvideBase onRequest');
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
        CommonService::getInstance()->log4PHP('ProvideBase afterAction');
    }
}