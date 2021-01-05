<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function afterAction(?string $actionName): void
    {
    }

    function test()
    {
        $res = null;

        $this->writeJson(200, null, $res);
    }

}