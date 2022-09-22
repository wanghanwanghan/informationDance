<?php

namespace App\HttpController\Service\XinDong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\MatchSimilarEnterprises;
use EasySwoole\EasySwoole\EasySwooleEvent;

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
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'MatchSimilarEnterprises_empty_Param'=>[
                        '$uid'=> $uid,
                        '$ys'=> $ys,
                        '$nic'=> $nic,
                        '$nx'=> $nx,
                        '$dy'=> $dy,
                    ]
                ])
            );
        }

        //self::pushToRedisList($uid,$ys,$nic,$nx,$dy);
        if(
            EasySwooleEvent::IsProductionEnv()
        ){
            return TaskService::getInstance()->create(new MatchSimilarEnterprises([$uid, $ys, $nic, $nx, $dy]));
        }
        else{
//            return TaskService::getInstance()->create(new MatchSimilarEnterprises([$uid, $ys, $nic, $nx, $dy]));
            return MatchSimilarEnterprises::pushToRedisListV2($uid, $ys, $nic, $nx, $dy);
        }
    }


}
