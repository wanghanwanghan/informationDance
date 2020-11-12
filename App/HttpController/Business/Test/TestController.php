<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HeHe\HeHeService;
use EasySwoole\Http\Message\UploadFile;

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
        $file = $this->request()->getUploadedFile('file');

        if ($file instanceof UploadFile)
        {

            (new HeHeService())->ocrImageToWord($file->getStream()->__toString());

        }else
        {
            var_dump(321);
        }
    }

}