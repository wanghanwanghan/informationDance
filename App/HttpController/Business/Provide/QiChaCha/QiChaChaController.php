<?php

namespace App\HttpController\Business\Provide\QiChaCha;

use App\HttpController\Business\Provide\ProvideBase;

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







}