<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
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

    function testPic()
    {
        $res = (new NewGraphService())
            ->setTitle('测试title')
            ->setLegends(['哈','哈哈','哈哈哈','哈哈哈哈','呵','呵呵','呵呵呵','呵呵呵呵'])
            ->line([10,20,30,40,50,60]);

        $this->writeJson(200,null,$res);
    }

}