<?php

namespace App\HttpController\Business\Api\QiChaCha;

use App\HttpController\Business\BusinessBase;

class QiChaChaBase extends BusinessBase
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
}