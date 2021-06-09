<?php

namespace App\HttpController\Service\MoveOut;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateTable\CreateTableService;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\traits\Singleton;

class MoveOutService extends ServiceBase
{
    use Singleton;

    //更新所有监控中的企业
    function updateDatabase(): void
    {
        //分公司:将园内企业名单作比对目标
        //提取信动T+1新企数据中的所有***分公司
        //将与园内企业主体名匹配的列为预警推荐目标

        //子公司（含股比66%以上）将园内企业名单作比对目标
        //提取信动T+1新企数据中的所有股东名称与股比信息
        //占比66%以上的股东名称且与园内企业主体名匹配的列为预警推荐目标

        //园内企业名单调用信动接口调出所有下属公司（含分公司、子公司、占比66%以上公司）
        //提取信动T+2变更数据中的所有变更地址项的公司名称
        //与园内企业下属公司名称匹配的将开始接口反推的园内企业列为预警推荐目标

        CreateTableService::getInstance()->information_dance_move_out_phone_entname();
        CreateTableService::getInstance()->information_dance_move_out_entname_rel();
    }

}
