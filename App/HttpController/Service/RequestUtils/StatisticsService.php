<?php

namespace App\HttpController\Service\RequestUtils;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Http\Request;

class StatisticsService extends ServiceBase
{
    private $pathInfo;
    private $token;

    function __construct(Request $request)
    {
        parent::__construct();

        $this->pathInfo = $request->getSwooleRequest()->server['path_info'];
        $this->token = $request->getHeaderLine('authorization');

        return true;
    }

    //通过
    function test()
    {
        return true;
    }




}
