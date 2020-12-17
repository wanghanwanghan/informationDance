<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\CreateTable\CreateTableService;

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
        CreateTableService::getInstance()->information_dance_request_user_info();
        CreateTableService::getInstance()->information_dance_request_api_info();
        CreateTableService::getInstance()->information_dance_request_user_api_relationship();
    }





}