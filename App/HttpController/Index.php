<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    function onRequest(?string $action): ?bool
    {
        mt_srand();
        \co::sleep(mt_rand(0,4));
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function index() {}
}