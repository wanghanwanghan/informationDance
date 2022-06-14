<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\FaDaDa\FaDaDaService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\HuiCheJian\HuiCheJianService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\QianQi\QianQiService;
use App\HttpController\Service\QiXiangYun\QiXiangYunService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function test20220614()
    {

    }
}