<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\HeHe\HeHeService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\NewGraph\NewGraphService;
use App\HttpController\Service\Queue\QueueConf;
use App\HttpController\Service\Queue\QueueService;
use EasySwoole\Http\Message\UploadFile;
use wanghanwanghan\someUtils\control;

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
        $res = User::create()->where('id',1111111)->all();

        $this->writeJson(200,null,(object)$res);
    }

}