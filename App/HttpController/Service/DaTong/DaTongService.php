<?php

namespace App\HttpController\Service\DaTong;

use App\HttpController\Service\ServiceBase;

class DaTongService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($res): array
    {
        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    function getList($url, $body)
    {
        return $this->checkRespFlag ? $this->checkResp() : $resp;
    }


}
