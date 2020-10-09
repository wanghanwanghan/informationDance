<?php

namespace App\HttpController\Service\Pay;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class ChargeService extends ServiceBase
{
    use Singleton;

    private $moduleInfo = [
        0=>['name' => '财务资产', 'desc' => '详情', 'basePrice' => 35],
        1=>['name' => '开庭公告', 'desc' => '详情', 'basePrice' => 1],
        2=>['name' => '判决文书', 'desc' => '详情', 'basePrice' => 1],
        3=>['name' => '法院公告', 'desc' => '详情', 'basePrice' => 1],
        4=>['name' => '执行公告', 'desc' => '详情', 'basePrice' => 1],
        5=>['name' => '失信公告', 'desc' => '详情', 'basePrice' => 1],
        6=>['name' => '司法查封冻结扣押', 'desc' => '详情', 'basePrice' => 1],
        7=>['name' => '司法拍卖', 'desc' => '详情', 'basePrice' => 1],
        8=>['name' => '欠税公告', 'desc' => '详情', 'basePrice' => 1],
        9=>['name' => '涉税处罚公示', 'desc' => '详情', 'basePrice' => 1],
        10=>['name' => '税务非正常户公示', 'desc' => '详情', 'basePrice' => 1],
        11=>['name' => '纳税信用等级', 'desc' => '详情', 'basePrice' => 1],
        12=>['name' => '税务登记', 'desc' => '详情', 'basePrice' => 1],
        13=>['name' => '税务许可', 'desc' => '详情', 'basePrice' => 1],
        200=>['name' => '简版报告', 'desc' => '', 'basePrice' => 400],
    ];

    function getModuleInfo(int $index = 500): array
    {
        if ($index == 500) return $this->moduleInfo;

        return $this->moduleInfo[$index];
    }


}
