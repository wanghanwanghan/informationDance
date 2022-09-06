<?php

namespace App\HttpController\Service\XinDong;

use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\MatchSimilarEnterprises;

class XinDongKeDongService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($code, $paging, $result, $msg): array
    {
        return $this->createReturn((int)$code, $paging, $result, $msg);
    }

    //匹配近似企业
    function MatchSimilarEnterprises(int $uid, string $ys, string $nic, string $nx, string $dy): bool
    {
        // 所有参数不可空
        // $ys=A10 $nic=F51 $nx=8 $dy=110108
        if (empty($uid) || empty($ys) || empty($nic) || empty($nx) || empty($dy)) {
            return false;
        }

        return TaskService::getInstance()->create(new MatchSimilarEnterprises([$uid, $ys, $nic, $nx, $dy]));
    }


}