<?php

namespace App\HttpController\Service\MoveOut;

use App\HttpController\Models\Api\MoveOutEntNameFinance;
use App\HttpController\Models\Api\MoveOutEntNameRel;
use App\HttpController\Models\Api\MoveOutPhoneEntName;
use App\HttpController\Models\EntDb\EntDbBasic;
use App\HttpController\Models\EntDb\EntDbInv;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\traits\Singleton;

class MoveOutService extends ServiceBase
{
    use Singleton;

    //更新所有监控中的企业
    function updateDatabase(): ?array
    {
        $list = MoveOutPhoneEntName::create()
            ->where('status', 1)
            ->where('expireTime', time(), '>')
            ->all();

        if (empty($list)) {
            CommonService::getInstance()->log4PHP('move out 列表是空');
            return null;
        }

        foreach ($list as $oneEnt) {
            //分公司:将园内企业名单作比对目标
            //提取信动T+1新企数据中的所有***分公司
            //将与园内企业主体名匹配的列为预警推荐目标
            $basicInfo = EntDbBasic::create()->where('ENTNAME', "{$oneEnt->entName}%", 'LIKE')->all();
            if (!empty($basicInfo)) {
                foreach ($basicInfo as $oneTarget) {
                    $relInfo = MoveOutEntNameRel::create()->where([
                        'pid' => $oneEnt->id,
                        'bid' => $oneTarget->id,
                        'type' => 'basic',
                    ])->get();
                    if (empty($relInfo)) {
                        MoveOutEntNameRel::create()->data([
                            'pid' => $oneEnt->id,
                            'bid' => $oneTarget->id,
                            'entName' => $oneTarget->ENTNAME,
                            'oldName' => $oneTarget->OLDNAME,
                            'code' => $oneTarget->SHXYDM,
                            'type' => 'basic',
                        ])->save();
                    } else {
                        $relInfo->update([
                            'entName' => $oneTarget->ENTNAME,
                            'oldName' => $oneTarget->OLDNAME,
                        ]);
                    }
                }
            }

            //子公司（含股比66%以上）将园内企业名单作比对目标
            //提取信动T+1新企数据中的所有股东名称与股比信息
            //占比66%以上的股东名称且与园内企业主体名匹配的列为预警推荐目标
            $invInfo = EntDbInv::create()->where('INV', $oneEnt->entName)->where('CONRATIO', '66', '>=')->all();
            if (!empty($invInfo)) {
                foreach ($invInfo as $oneTarget) {
                    $relInfo = MoveOutEntNameRel::create()->where([
                        'pid' => $oneEnt->id,
                        'iid' => $oneTarget->id,
                        'type' => 'inv',
                    ])->get();
                    if (empty($relInfo)) {
                        MoveOutEntNameRel::create()->data([
                            'pid' => $oneEnt->id,
                            'iid' => $oneTarget->id,
                            'entName' => $oneTarget->ENTNAME,
                            'code' => $oneTarget->SHXYDM,
                            'type' => 'inv',
                        ])->save();
                    } else {
                        $relInfo->update([
                            'entName' => $oneTarget->ENTNAME,
                        ]);
                    }
                }
            }

            $check = MoveOutEntNameFinance::create()->where('entName', $oneEnt->entName)->get();

            if (empty($check) || Carbon::now()->format('md') - 0 === 1231) {
                //为空，或者每年年底跑一次，年底跑可以更新

                //财务表现：园内企业营收、利润或纳税等关键指标，近3年连续下降100%或近2年下降200%的园内企业，列为预警推荐目标
                $info = (new LongXinService())->setCheckRespFlag(true)->getFinanceData([
                    'entName' => $oneEnt->entName,
                    'code' => $oneEnt->code,
                    'dataCount' => 4,
                    'beginYear' => date('Y') - 2,
                ], false);

                if ($info['code'] === 200 && !empty($info['result'])) {
                    foreach ($info['result'] as $year => $val) {
                        CommonService::getInstance()->log4PHP($year);
                    }
                }

                //人数表现：园内企业缴纳社保人数，近3年连续下降100%或近2年下降200%的园内企业，列为预警推荐目标

            }
        }

        return null;
    }

}
