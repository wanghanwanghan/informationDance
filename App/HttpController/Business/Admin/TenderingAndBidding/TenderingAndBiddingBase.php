<?php

namespace App\HttpController\Business\Admin\TenderingAndBidding;

use App\HttpController\Business\BusinessBase;

class TenderingAndBiddingBase extends BusinessBase
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