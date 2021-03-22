<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\LongXin\LongXinService;

class xds
{
    function cwScore($entName): ?array
    {
        $arr = (new LongXinService())->setCheckRespFlag(true)->getFinanceData([
            'entName' => $entName,
            'code' => '',
            'beginYear' => date('Y') - 1,
            'dataCount' => 4,
        ], false);

//        if (!isset($arr['code']) || $arr['code'] !== 200 || empty($res['data']))
//            return null;


        return $arr;
    }


}
