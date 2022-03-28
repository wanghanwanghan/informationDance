<?php

namespace App\HttpController\Business\Provide\SaibopengkeAdmin;

use App\HttpController\Business\Provide\ProvideBase;

class SaibopengkeAdminController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function uploadEntList(){

    }
}