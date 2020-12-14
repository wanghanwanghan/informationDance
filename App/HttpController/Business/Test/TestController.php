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
            ->setTitle(control::randomUserName())
            ->setLegends(['哈','哈哈','哈哈哈','哈哈哈哈','呵','呵呵','呵呵呵','呵呵呵呵'])
            ->setXLabels(['数量1','数量2','数量3','数量4','数量5','数量6'])
            ->line([
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
                [control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3),control::randNum(3)],
            ]);

        $this->writeJson(200,null,$res);
    }

}