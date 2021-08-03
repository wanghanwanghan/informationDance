<?php

namespace App\HttpController\Business\Admin\GroceryStore;

use App\HttpController\Business\BusinessBase;

class GroceryStoreBase extends BusinessBase
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