<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\LongXin\LongXinService;

class xds
{
    function cwScore($entName): ?array
    {
        $arr = (new LongXinService())->getFinanceData([
            'entName' => $entName,
            'code' => '',
            'beginYear' => date('Y') - 1,
            'dataCount' => 4,
        ], false);


        return empty($arr) ? null : $arr;
    }


}
