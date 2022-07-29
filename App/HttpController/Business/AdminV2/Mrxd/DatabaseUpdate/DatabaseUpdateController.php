<?php

namespace App\HttpController\Business\AdminV2\Mrxd\DatabaseUpdate;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;

class DatabaseUpdateController extends ControllerBase
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