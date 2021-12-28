<?php

namespace App\HttpController\Service\JingZhun;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class JingZhunService extends ServiceBase
{
    use Singleton;

    private $base_url = 'https://data-api.jingdata.com/x/api/investment/list';


    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($res): array
    {
        $code = $pagine = $result = $msg = null;


        return $this->createReturn($code, $pagine, $result, $msg);
    }


}
