<?php

namespace App\HttpController\Business\Admin\CheXianWuliu;

use App\HttpController\Business\BusinessBase;

class CheXianWuliuBase extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
}