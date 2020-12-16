<?php

namespace App\HttpController\Business\Provide;

use App\HttpController\Index;
use App\HttpController\Service\CreateConf;

class ProvideBase extends Index
{
    public $qccListUrl;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $this->qccListUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}