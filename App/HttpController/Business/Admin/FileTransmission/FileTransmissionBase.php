<?php

namespace App\HttpController\Business\Admin\FileTransmission;

use App\HttpController\Business\BusinessBase;

class FileTransmissionBase extends BusinessBase
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