<?php

namespace App\HttpController\Business\AdminNew\Mrxd\Menu;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;

class MenuController extends ControllerBase
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