<?php

namespace App\HttpController\Service\Pay;

use App\HttpController\Service\ServiceBase;

class PayBase extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }
}
