<?php

namespace App\HttpController\Business\Api\TaoShu;

class TaoShuController extends TaoShuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function index()
    {
        return true;
    }
}