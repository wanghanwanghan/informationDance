<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;

class xds
{
    //0资产总额 ASSGRO
    //1负债总额 LIAGRO
    //2营业总收入 VENDINC
    //3主营业务收入 MAIBUSINC
    //4利润总额 PROGRO
    //5净利润 NETINC
    //6纳税总额 RATGRO
    //7所有者权益 TOTEQU
    //8社保人数 SOCNUM

    //9净资产 C_ASSGROL
    //10平均资产总额 A_ASSGROL
    //11平均净资产 CA_ASSGRO
    //12净利率 C_INTRATESL
    //13资产周转率 ATOL
    //14总资产净利率 ASSGRO_C_INTRATESL
    //15企业人均产值 A_VENDINCL
    //16企业人均盈利 A_PROGROL
    //17总资产回报率 ROA ROAL
    //18净资产回报率 ROE (A) ROE_AL
    //19净资产回报率 ROE (B) ROE_BL
    //20资产负债率 DEBTL
    //21权益乘数 EQUITYL
    //22主营业务比率 MAIBUSINC_RATIOL

    //23净资产负债率 NALR
    //24营业利润率 OPM
    //25资本保值增值率 ROCA
    //26营业净利率 NOR
    //27总资产利润率 PMOTA
    //28税收负担率 TBR
    //29权益乘数 EQUITYL_new

    //30资产总额同比 ASSGRO_yoy
    //31负债总额同比 LIAGRO_yoy
    //32营业总收入同比 VENDINC_yoy
    //33主营业务收入同比 MAIBUSINC_yoy
    //34利润总额同比 PROGRO_yoy
    //35净利润同比 NETINC_yoy
    //36纳税总额同比 RATGRO_yoy
    //37所有者权益同比 TOTEQU_yoy

    //38税收负担率 TBR_new

    function __construct()
    {
        $this->ld = CreateConf::getInstance()->getConf('longdun.baseUrl');
    }

    //计算各项分数
    function cwScore($entName): ?array
    {
        $arr = (new LongXinService())->setCheckRespFlag(true)->getFinanceData([
            'entName' => $entName,
            'code' => '',
            'beginYear' => date('Y') - 2,
            'dataCount' => 4,
        ], false);

        if ($arr['code'] !== 200 || empty($arr['result'])) {
            return null;
        }

        //年份转string
        $tmp = [];
        foreach ($arr['result'] as $year => $val) {
            $tmp[$year . ''] = $val;
        }
        $arr['result'] = $tmp;

        $score = [];

        //企业营收增长能力评分 33主营业务收入同比 MAIBUSINC_yoy
        $score['MAIBUSINC_yoy'] = $this->MAIBUSINC_yoy($arr['result']);

        //总资产增长状况 30资产总额同比 ASSGRO_yoy
        $score['ASSGRO_yoy'] = $this->ASSGRO_yoy($arr['result']);

        //企业盈利能力评分 主营业务净利润率 5净利润 / 3主营业务收入
        $score['PROGRO'] = $this->PROGRO($arr['result']);

        //企业利润增长能力评分 34利润总额同比 PROGRO_yoy
        $score['PROGRO_yoy'] = $this->PROGRO_yoy($arr['result']);

        //企业纳税能力综合评分 6纳税总额
        $score['RATGRO'] = $this->RATGRO($arr['result']);

        //税负强度 28税收负担率 TBR
        $score['TBR'] = $this->TBR($arr['result']);

        //企业资产收益评分 总资产收益率 = 5净利润 / 10平均资产总额
        $score['ASSGROPROFIT_REL'] = $this->ASSGROPROFIT_REL($arr['result']);

        //资产回报能力 5净利润 / 11平均净资产
        $score['ASSETS'] = $this->ASSETS($arr['result']);

        //企业资本保值状况评分 7期末所有者权益 / 7期初所有者权益 TOTEQU
        $score['TOTEQU'] = $this->TOTEQU($arr['result']);

        //企业主营业务健康度评分 20资产负债率 = 1负债总额 / 0资产总额
        $score['DEBTL_H'] = $this->DEBTL_H($arr['result'], $entName);

        //企业资产负债状况评分 20资产负债率 = 1负债总额 / 0资产总额
        $score['DEBTL'] = $this->DEBTL($arr['result']);

        //资产周转能力 2营业收入 / 10平均资产总额
        $score['ATOL'] = $this->ATOL($arr['result']);

        //企业人均产能评分 3主营业务收入 / 8缴纳社保人数人均
        $score['PERCAPITA_C'] = $this->PERCAPITA_C($arr['result']);

        //企业人均盈利能力评分 5净利润 / 8缴纳社保人数
        $score['PERCAPITA_Y'] = $this->PERCAPITA_Y($arr['result']);

        //还款能力 20资产负债率 DEBTL 60% && 16企业人均盈利 A_PROGROL 40%
        $score['RepaymentAbility'] = $this->RepaymentAbility($arr['result']);

        //担保能力 (0资产总额 - 1负债总额 - 股权质押接口的出质股权数额 - 动产抵押接口的被担保主债权数额 - 对外担保接口的主债权数额) / 0资产总额
        $score['GuaranteeAbility'] = $this->GuaranteeAbility($arr['result'], $entName);

        //税负强度 28税收负担率 TBR_new
        $score['TBR_new'] = $this->TBR_new($arr['result']);

        return $score;
    }

    //企业资产收益评分 总资产收益率 = 5净利润 / 10平均资产总额
    private function ASSGROPROFIT_REL($data): array
    {
        $r = [
            'name' => '企业资产收益能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['A_ASSGROL'])) {
                $arr['A_ASSGROL'] == 0 ? $val = 0 : $val = round($arr['NETINC'] / $arr['A_ASSGROL'] * 100);
                if ($val <= -10) {
                    $score = 4;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 8;
                } elseif ($val >= -5 && $val <= -1) {
                    $score = 11;
                } elseif ($val >= -1 && $val <= 0) {
                    $score = 16;
                } elseif ($val >= 0 && $val <= 1.2) {
                    $score = 26;
                } elseif ($val >= 1.21 && $val <= 2.2) {
                    $score = 31;
                } elseif ($val >= 2.21 && $val <= 3.3) {
                    $score = 35;
                } elseif ($val >= 3.31 && $val <= 5.5) {
                    $score = 42;
                } elseif ($val >= 5.51 && $val <= 8.3) {
                    $score = 56;
                } elseif ($val >= 8.31 && $val <= 10.5) {
                    $score = 72;
                } elseif ($val >= 10.51 && $val <= 20) {
                    $score = 85;
                } elseif ($val >= 20.1 && $val <= 30) {
                    $score = 92;
                } elseif ($val >= 30.1 && $val <= 50) {
                    $score = 93;
                } elseif ($val >= 50.1 && $val <= 100) {
                    $score = 94.5;
                } elseif ($val >= 100.1 && $val <= 300) {
                    $score = 97.5;
                } elseif ($val >= 300) {
                    $score = 99;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //企业资产负债状况评分 20资产负债率 = 1负债总额 / 0资产总额
    private function DEBTL($data): array
    {
        $r = [
            'name' => '企业资产经营健康度',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['LIAGRO']) && is_numeric($arr['ASSGRO'])) {
                if ($arr['ASSGRO'] != 0) {
                    $val = round($arr['LIAGRO'] / $arr['ASSGRO'] * 100);
                    if ($val == 0) {
                        $score = 99.5;
                    } elseif ($val >= 0.1 && $val <= 5) {
                        $score = 98;
                    } elseif ($val >= 5.1 && $val <= 10) {
                        $score = 92.5;
                    } elseif ($val >= 10.1 && $val <= 20) {
                        $score = 86.5;
                    } elseif ($val >= 20.1 && $val <= 30) {
                        $score = 81;
                    } elseif ($val >= 30.1 && $val <= 40) {
                        $score = 77.5;
                    } elseif ($val >= 40.1 && $val <= 50) {
                        $score = 70;
                    } elseif ($val >= 50.1 && $val <= 60) {
                        $score = 64;
                    } elseif ($val >= 60.1 && $val <= 70) {
                        $score = 55.5;
                    } elseif ($val >= 70.1 && $val <= 80) {
                        $score = 42.5;
                    } elseif ($val >= 80.1 && $val <= 90) {
                        $score = 39;
                    } elseif ($val >= 90.1 && $val <= 100) {
                        $score = 28.5;
                    } elseif ($val >= 100.1 && $val <= 150) {
                        $score = 17;
                    } elseif ($val >= 150) {
                        $score = 11;
                    } else {
                        $score = null;
                    }
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = intval($score * 0.85);
                } else {
                    $score = 11;
                    $r['year'] = $year;
                    $r['val'] = 150;
                    $r['score'] = intval($score * 0.85);
                }
                break;
            }
        }

        return $r;
    }

    //企业主营业务健康度评分 20资产负债率 = 1负债总额 / 0资产总额
    private function DEBTL_H($data, $entName): array
    {
        $r = [
            'name' => '企业主营业务健康度',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        //实际控制人
        $sjkzr = (new LongDunService())->setCheckRespFlag(true)
            ->get($this->ld . 'Beneficiary/GetBeneficiary', [
                'companyName' => $entName,
                'percent' => 0,
                'mode' => 0,
            ]);

        $temp = [];
        if ($sjkzr['code'] === 200 && !empty($sjkzr['result'])) {
            if (count($sjkzr['result']['BreakThroughList']) > 0) {
                $total = current($sjkzr['result']['BreakThroughList']);
                $total = substr($total['TotalStockPercent'], 0, -1);
                if ($total >= 50) {
                    //如果第一个人就是大股东了，就直接返回
                    $temp = [];
                } else {
                    //把返回的所有人加起来和100做减法，求出坑
                    $hole = 100;
                    foreach ($sjkzr['result']['BreakThroughList'] as $key => $val) {
                        $hole -= substr($val['TotalStockPercent'], 0, -1);
                    }
                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
                    if ($total > $hole) $temp = $sjkzr['result']['BreakThroughList'][0];
                }
            }
        }

        //实际控制人算不算进权重
        !empty($temp) ? $type = true : $type = false;

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['LIAGRO']) && is_numeric($arr['ASSGRO'])) {
                if ($arr['ASSGRO'] != 0) {
                    $val = round($arr['LIAGRO'] / $arr['ASSGRO'] * 100);
                    if ($val == 0) {
                        $score = 99.5;
                    } elseif ($val >= 0.1 && $val <= 5) {
                        $score = 98;
                    } elseif ($val >= 5.1 && $val <= 10) {
                        $score = 92.5;
                    } elseif ($val >= 10.1 && $val <= 20) {
                        $score = 86.5;
                    } elseif ($val >= 20.1 && $val <= 30) {
                        $score = 81;
                    } elseif ($val >= 30.1 && $val <= 40) {
                        $score = 77.5;
                    } elseif ($val >= 40.1 && $val <= 50) {
                        $score = 70;
                    } elseif ($val >= 50.1 && $val <= 60) {
                        $score = 64;
                    } elseif ($val >= 60.1 && $val <= 70) {
                        $score = 55.5;
                    } elseif ($val >= 70.1 && $val <= 80) {
                        $score = 42.5;
                    } elseif ($val >= 80.1 && $val <= 90) {
                        $score = 39;
                    } elseif ($val >= 90.1 && $val <= 100) {
                        $score = 28.5;
                    } elseif ($val >= 100.1 && $val <= 150) {
                        $score = 17;
                    } elseif ($val >= 150) {
                        $score = 11;
                    } else {
                        $score = null;
                    }
                    $type && $score !== null ?
                        $score = intval($score * 0.7 + 100 * 0.3) :
                        $score = intval($score * 0.9);
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = $score;
                } else {
                    $score = 11;
                    $type ?
                        $score = intval($score * 0.7 + 100 * 0.3) :
                        $score = intval($score * 0.9);
                    $r['year'] = $year;
                    $r['val'] = 150;
                    $r['score'] = intval($score * 0.9);
                }
                break;
            }
        }

        return $r;
    }

    //企业盈利能力评分 主营业务净利润率 5净利润 / 3主营业务收入
    private function PROGRO($data): array
    {
        $r = [
            'name' => '企业盈利能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        //
        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['MAIBUSINC'])) {
                $arr['MAIBUSINC'] == 0 ? $val = 0 : $val = round($arr['NETINC'] / $arr['MAIBUSINC'] * 100);
                if ($val <= 0) {
                    $score = 10;
                } elseif ($val >= 1 && $val <= 2) {
                    $score = 15;
                } elseif ($val >= 3 && $val <= 5) {
                    $score = 21;
                } elseif ($val >= 6 && $val <= 8) {
                    $score = 30;
                } elseif ($val >= 9 && $val <= 10) {
                    $score = 41;
                } elseif ($val > 10 && $val <= 100) {
                    //每多5%加8分
                    $score = intval($val / 5) * 8;
                    $score <= 97 ?: $score = 97;
                } elseif ($val > 100) {
                    $score = 97;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //企业营收增长能力评分 33主营业务收入同比 MAIBUSINC_yoy
    private function MAIBUSINC_yoy($data): array
    {
        $r = [
            'name' => '企业成长性状况',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            is_numeric($arr['MAIBUSINC_yoy']) ?: $arr['MAIBUSINC_yoy'] = 0;
            if (is_numeric($arr['MAIBUSINC_yoy'])) {
                $val = round($arr['MAIBUSINC_yoy'] * 100);
                if ($val <= -50) {
                    $score = 4;
                } elseif ($val >= -50 && $val <= -21) {
                    $score = 8;
                } elseif ($val >= -20 && $val <= -11) {
                    $score = 12;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 17;
                } elseif ($val >= -5 && $val <= 0) {
                    $score = 23;
                } elseif ($val >= 0 && $val <= 5) {
                    $score = 27;
                } elseif ($val >= 6 && $val <= 10) {
                    $score = 31.5;
                } elseif ($val >= 11 && $val <= 25) {
                    $score = 35;
                } elseif ($val >= 26 && $val <= 30) {
                    $score = 40.5;
                } elseif ($val >= 31 && $val <= 50) {
                    $score = 54.5;
                } elseif ($val >= 51 && $val <= 70) {
                    $score = 72;
                } elseif ($val >= 71 && $val <= 100) {
                    $score = 83.5;
                } elseif ($val >= 101 && $val <= 200) {
                    $score = 92.5;
                } elseif ($val >= 201 && $val <= 500) {
                    $score = 95.5;
                } elseif ($val >= 500) {
                    $score = 97.5;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //企业利润增长能力评分 34利润总额同比 PROGRO_yoy
    private function PROGRO_yoy($data): array
    {
        $r = [
            'name' => '企业盈利可持续能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['PROGRO_yoy'])) {
                $val = round($arr['PROGRO_yoy'] * 100);
                if ($val <= -50) {
                    $score = 4;
                } elseif ($val >= -50 && $val <= -21) {
                    $score = 8;
                } elseif ($val >= -20 && $val <= -11) {
                    $score = 11;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 16;
                } elseif ($val >= -5 && $val <= 0) {
                    $score = 21;
                } elseif ($val >= 0 && $val <= 5) {
                    $score = 26;
                } elseif ($val >= 6 && $val <= 10) {
                    $score = 31;
                } elseif ($val >= 11 && $val <= 25) {
                    $score = 35;
                } elseif ($val >= 26 && $val <= 30) {
                    $score = 42;
                } elseif ($val >= 31 && $val <= 50) {
                    $score = 56;
                } elseif ($val >= 51 && $val <= 70) {
                    $score = 72;
                } elseif ($val >= 71 && $val <= 100) {
                    $score = 85;
                } elseif ($val >= 101 && $val <= 200) {
                    $score = 92;
                } elseif ($val >= 201 && $val <= 500) {
                    $score = 94;
                } elseif ($val >= 500) {
                    $score = 97;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //企业资本保值状况评分 7期末所有者权益 / 7期初所有者权益 TOTEQU
    private function TOTEQU($data): array
    {
        $r = [
            'name' => '企业资本保值状况',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['TOTEQU']) && is_numeric($data[($year - 1) . '']['TOTEQU'])) {
                if ($data[($year - 1) . '']['TOTEQU'] != 0) {
                    $val = round($arr['TOTEQU'] / $data[($year - 1) . '']['TOTEQU'] * 100);
                    if ($val <= 50) {
                        $score = 22;
                    } elseif ($val >= 50 && $val <= 100) {
                        $score = intval($val / 10) * 4;
                    } elseif ($val >= 101 && $val <= 110) {
                        $score = 46.5;
                    } elseif ($val >= 110.1 && $val <= 130) {
                        $score = 64;
                    } elseif ($val >= 130.1 && $val <= 150) {
                        $score = 70.5;
                    } elseif ($val >= 150.1 && $val <= 180) {
                        $score = 80.5;
                    } elseif ($val >= 180.1 && $val <= 200) {
                        $score = 84;
                    } elseif ($val >= 220.1 && $val <= 300) {
                        $score = 88.5;
                    } elseif ($val >= 300.1 && $val <= 500) {
                        $score = 93.5;
                    } elseif ($val >= 500.1 && $val <= 1000) {
                        $score = 96.5;
                    } elseif ($val >= 1000) {
                        $score = 99;
                    } else {
                        $score = null;
                    }
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = $score;
                    break;
                }
            }
        }

        return $r;
    }

    //企业人均产能评分 3主营业务收入 / 8缴纳社保人数
    private function PERCAPITA_C($data): array
    {
        $r = [
            'name' => '企业人均产能',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['MAIBUSINC']) && is_numeric($arr['SOCNUM'])) {
                if ($arr['SOCNUM'] > 0) {
                    $val = round($arr['MAIBUSINC'] / $arr['SOCNUM']);
                    if ($val <= 10) {
                        $score = 15.5;
                    } elseif ($val >= 10 && $val <= 20) {
                        $score = 34.5;
                    } elseif ($val >= 20.1 && $val <= 30) {
                        $score = 44;
                    } elseif ($val >= 30.1 && $val <= 45) {
                        $score = 51.5;
                    } elseif ($val >= 45.1 && $val <= 60) {
                        $score = 65;
                    } elseif ($val >= 60.1 && $val <= 100) {
                        $score = 69.5;
                    } elseif ($val >= 100.1 && $val <= 150) {
                        $score = 72;
                    } elseif ($val >= 150.1 && $val <= 300) {
                        $score = 78;
                    } elseif ($val >= 300.1 && $val <= 500) {
                        $score = 80.5;
                    } elseif ($val >= 500.1 && $val <= 800) {
                        $score = 84;
                    } elseif ($val >= 800.1 && $val <= 1000) {
                        $score = 90.5;
                    } elseif ($val >= 1000.1 && $val <= 5000) {
                        $score = 93.5;
                    } elseif ($val >= 5000.1 && $val <= 10000) {
                        $score = 98;
                    } elseif ($val >= 10000) {
                        $score = 99.5;
                    } else {
                        $score = null;
                    }
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = intval($score * 0.7);
                    break;
                }
            }
        }

        return $r;
    }

    //企业人均盈利能力评分 5净利润 / 8缴纳社保人数
    private function PERCAPITA_Y($data): array
    {
        $r = [
            'name' => '企业人均创收能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['SOCNUM'])) {
                if ($arr['SOCNUM'] > 0) {
                    $val = round($arr['NETINC'] / $arr['SOCNUM']);
                    if ($val <= -200) {
                        $score = 12;
                    } elseif ($val >= -200 && $val <= 0) {
                        $score = 19.5;
                    } elseif ($val >= 0 && $val <= 5) {
                        $score = 28;
                    } elseif ($val >= 5.1 && $val <= 10) {
                        $score = 33.5;
                    } elseif ($val >= 10.1 && $val <= 20) {
                        $score = 48;
                    } elseif ($val >= 20.1 && $val <= 30) {
                        $score = 54.5;
                    } elseif ($val >= 30.1 && $val <= 50) {
                        $score = 69;
                    } elseif ($val >= 50.1 && $val <= 80) {
                        $score = 75;
                    } elseif ($val >= 80.1 && $val <= 100) {
                        $score = 90.5;
                    } elseif ($val >= 100.1 && $val <= 500) {
                        $score = 93.5;
                    } elseif ($val >= 500.1 && $val <= 1000) {
                        $score = 98;
                    } elseif ($val >= 1000.1 && $val <= 10000) {
                        $score = 99;
                    } elseif ($val >= 10000) {
                        $score = 99.5;
                    } else {
                        $score = null;
                    }
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = $score;
                    break;
                }
            }
        }

        return $r;
    }

    //企业纳税能力综合评分 6纳税总额
    private function RATGRO($data): array
    {
        $r = [
            'name' => '企业税收贡献能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['RATGRO'])) {
                $val = round($arr['RATGRO']);
                if ($val >= 0.01 && $val <= 3) {
                    $score = 10;
                } elseif ($val >= 3.1 && $val <= 5) {
                    $score = 20;
                } elseif ($val >= 5.1 && $val <= 50) {
                    $score = 31.5;
                } elseif ($val >= 50.1 && $val <= 500) {
                    $score = 42.5;
                } elseif ($val >= 500.1 && $val <= 1000) {
                    $score = 50;
                } elseif ($val >= 1000.1 && $val <= 10000) {
                    $score = 62;
                } elseif ($val >= 10000.1 && $val <= 50000) {
                    $score = 71;
                } elseif ($val >= 50000.1 && $val <= 100000) {
                    $score = 82;
                } elseif ($val >= 100000.1 && $val <= 1000000) {
                    $score = 93;
                } elseif ($val >= 1000000) {
                    $score = 96.5;
                } else {
                    $score = 1;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //资产回报能力 5净利润 / 11平均净资产
    private function ASSETS($data): array
    {
        $r = [
            'name' => '企业资产回报能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['CA_ASSGRO'])) {
                if ($arr['CA_ASSGRO'] != 0) {
                    $val = round($arr['NETINC'] / $arr['CA_ASSGRO'] * 100);
                    if ($val <= -10) {
                        $score = 4;
                    } elseif ($val >= -10 && $val <= -6) {
                        $score = 8;
                    } elseif ($val >= -5 && $val <= -1) {
                        $score = 11;
                    } elseif ($val >= -1 && $val <= 0) {
                        $score = 15;
                    } elseif ($val >= 0 && $val <= 3.3) {
                        $score = 35;
                    } elseif ($val >= 3.31 && $val <= 5.5) {
                        $score = 41;
                    } elseif ($val >= 5.51 && $val <= 8.3) {
                        $score = 54.5;
                    } elseif ($val >= 8.31 && $val <= 15.5) {
                        $score = 68.5;
                    } elseif ($val >= 15.51 && $val <= 20) {
                        $score = 85;
                    } elseif ($val >= 20.1 && $val <= 30) {
                        $score = 88.5;
                    } elseif ($val >= 30.1 && $val <= 50) {
                        $score = 93;
                    } elseif ($val >= 50.1 && $val <= 80) {
                        $score = 95.5;
                    } elseif ($val >= 80.1 && $val <= 100) {
                        $score = 98.5;
                    } elseif ($val >= 100) {
                        $score = 99.5;
                    } else {
                        $score = null;
                    }
                    $r['year'] = $year;
                    $r['val'] = $val;
                    $r['score'] = $score;
                    break;
                }
            }
        }

        return $r;
    }

    //资产周转能力 2营业收入 / 10平均资产总额
    private function ATOL($data): array
    {
        $r = [
            'name' => '企业资产周转能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['VENDINC']) && $arr['VENDINC'] > 0 && is_numeric($arr['A_ASSGROL'])) {
                $arr['A_ASSGROL'] == 0 ? $val = 0 : $val = round($arr['VENDINC'] / $arr['A_ASSGROL'] * 100);
                if ($val >= 0 && $val <= 1) {
                    $score = 12.5;
                } elseif ($val >= 1.1 && $val <= 2) {
                    $score = 18.5;
                } elseif ($val >= 2.1 && $val <= 3) {
                    $score = 26;
                } elseif ($val >= 3.1 && $val <= 5) {
                    $score = 31;
                } elseif ($val >= 5.1 && $val <= 10) {
                    $score = 35;
                } elseif ($val >= 10.1 && $val <= 20) {
                    $score = 42;
                } elseif ($val >= 20.1 && $val <= 35) {
                    $score = 56;
                } elseif ($val >= 35.1 && $val <= 50) {
                    $score = 72;
                } elseif ($val >= 50.1 && $val <= 60) {
                    $score = 85;
                } elseif ($val >= 60.1 && $val <= 70) {
                    $score = 92;
                } elseif ($val >= 70.1 && $val <= 80) {
                    $score = 93;
                } elseif ($val >= 80.1 && $val <= 100) {
                    $score = 94.5;
                } elseif ($val >= 100.1 && $val <= 300) {
                    $score = 97.5;
                } elseif ($val >= 300) {
                    $score = 99;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = intval($score * 0.8);
                break;
            }
        }

        return $r;
    }

    //总资产增长状况 30资产总额同比 ASSGRO_yoy
    private function ASSGRO_yoy($data): array
    {
        $r = [
            'name' => '企业资产增长状况',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            is_numeric($arr['ASSGRO_yoy']) ?: $arr['ASSGRO_yoy'] = 0;
            if (is_numeric($arr['ASSGRO_yoy'])) {
                $val = round($arr['ASSGRO_yoy'] * 100);
                if ($val < 0) {
                    $score = 5;
                } elseif ($val >= 0 && $val <= 1) {
                    $score = 9;
                } elseif ($val >= 1.1 && $val <= 5) {
                    $score = 15.5;
                } elseif ($val >= 5.1 && $val <= 10) {
                    $score = 21;
                } elseif ($val >= 10.1 && $val <= 30) {
                    $score = 28.5;
                } elseif ($val >= 30.1 && $val <= 40) {
                    $score = 33;
                } elseif ($val >= 40.1 && $val <= 50) {
                    $score = 42;
                } elseif ($val >= 50.1 && $val <= 60) {
                    $score = 53;
                } elseif ($val >= 60.1 && $val <= 70) {
                    $score = 63;
                } elseif ($val >= 70.1 && $val <= 80) {
                    $score = 73;
                } elseif ($val >= 80.1 && $val <= 100) {
                    $score = 86;
                } elseif ($val >= 100.1 && $val <= 200) {
                    $score = 91;
                } elseif ($val >= 200.1 && $val <= 500) {
                    $score = 95;
                } elseif ($val >= 500) {
                    $score = 98;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //税负强度 28税收负担率 TBR
    private function TBR($data): array
    {
        $r = [
            'name' => '企业税负强度',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['TBR'])) {
                $val = round($arr['TBR'] * 100);
                if ($val >= 0 && $val <= 1) {
                    $score = 29;
                } elseif ($val >= 1.1 && $val <= 1.5) {
                    $score = 38;
                } elseif ($val >= 1.51 && $val <= 3) {
                    $score = 47;
                } elseif ($val >= 3.1 && $val <= 6) {
                    $score = 56;
                } elseif ($val >= 6.1 && $val <= 10) {
                    $score = 65;
                } elseif ($val >= 10.1 && $val <= 15) {
                    $score = 74;
                } elseif ($val >= 15.1 && $val <= 20) {
                    $score = 83;
                } elseif ($val >= 20.1 && $val <= 30) {
                    $score = 92;
                } elseif ($val >= 30) {
                    $score = 96;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //税负强度 38税收负担率 TBR_new
    private function TBR_new($data): array
    {
        $r = [
            'name' => '企业税负强度 new',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['TBR_new'])) {
                $val = round($arr['TBR_new'] * 100);
                if ($val >= 0 && $val <= 1) {
                    $score = 29;
                } elseif ($val >= 1.1 && $val <= 1.5) {
                    $score = 38;
                } elseif ($val >= 1.51 && $val <= 3) {
                    $score = 47;
                } elseif ($val >= 3.1 && $val <= 6) {
                    $score = 56;
                } elseif ($val >= 6.1 && $val <= 10) {
                    $score = 65;
                } elseif ($val >= 10.1 && $val <= 15) {
                    $score = 74;
                } elseif ($val >= 15.1 && $val <= 20) {
                    $score = 83;
                } elseif ($val >= 20.1 && $val <= 30) {
                    $score = 92;
                } elseif ($val >= 30) {
                    $score = 96;
                } else {
                    $score = null;
                }
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = $score;
                break;
            }
        }

        return $r;
    }

    //还款能力 20资产负债率 DEBTL 60% && 16企业人均盈利 A_PROGROL 40%
    private function RepaymentAbility($data): array
    {
        $r = [
            'name' => '企业还款能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        foreach ($data as $year => $arr) {
            (is_numeric($arr['DEBTL']) && $arr['DEBTL'] > 0) ?: $arr['DEBTL'] = 1.51;
            (is_numeric($arr['A_PROGROL']) && $arr['A_PROGROL'] > 0) ?: $arr['A_PROGROL'] = 0;
            if (is_numeric($arr['DEBTL']) && is_numeric($arr['A_PROGROL'])) {
                $DEBTL = round($arr['DEBTL'] * 100);
                $A_PROGROL = round($arr['A_PROGROL']);

                if ($DEBTL == 0) {
                    $score1 = 99.5;
                } elseif ($DEBTL >= 0.1 && $DEBTL <= 5) {
                    $score1 = 98;
                } elseif ($DEBTL >= 5.1 && $DEBTL <= 10) {
                    $score1 = 92.5;
                } elseif ($DEBTL >= 10.1 && $DEBTL <= 20) {
                    $score1 = 86.5;
                } elseif ($DEBTL >= 20.1 && $DEBTL <= 30) {
                    $score1 = 81;
                } elseif ($DEBTL >= 30.1 && $DEBTL <= 40) {
                    $score1 = 77.5;
                } elseif ($DEBTL >= 40.1 && $DEBTL <= 50) {
                    $score1 = 70;
                } elseif ($DEBTL >= 50.1 && $DEBTL <= 60) {
                    $score1 = 64;
                } elseif ($DEBTL >= 60.1 && $DEBTL <= 70) {
                    $score1 = 55.5;
                } elseif ($DEBTL >= 70.1 && $DEBTL <= 80) {
                    $score1 = 42.5;
                } elseif ($DEBTL >= 80.1 && $DEBTL <= 90) {
                    $score1 = 39;
                } elseif ($DEBTL >= 90.1 && $DEBTL <= 100) {
                    $score1 = 28.5;
                } elseif ($DEBTL >= 100.1 && $DEBTL <= 150) {
                    $score1 = 17;
                } elseif ($DEBTL >= 150) {
                    $score1 = 11;
                } else {
                    $score1 = null;
                }

                if ($A_PROGROL <= -200) {
                    $score2 = 8;
                } elseif ($A_PROGROL >= -200 && $A_PROGROL <= 0) {
                    $score2 = 15;
                } elseif ($A_PROGROL >= 0 && $A_PROGROL <= 5) {
                    $score2 = 28;
                } elseif ($A_PROGROL >= 5.1 && $A_PROGROL <= 10) {
                    $score2 = 33.5;
                } elseif ($A_PROGROL >= 10.1 && $A_PROGROL <= 20) {
                    $score2 = 48;
                } elseif ($A_PROGROL >= 20.1 && $A_PROGROL <= 30) {
                    $score2 = 54.5;
                } elseif ($A_PROGROL >= 30.1 && $A_PROGROL <= 50) {
                    $score2 = 69;
                } elseif ($A_PROGROL >= 50.1 && $A_PROGROL <= 80) {
                    $score2 = 75;
                } elseif ($A_PROGROL >= 80.1 && $A_PROGROL <= 100) {
                    $score2 = 90.5;
                } elseif ($A_PROGROL >= 100.1 && $A_PROGROL <= 500) {
                    $score2 = 93.5;
                } elseif ($A_PROGROL >= 500.1 && $A_PROGROL <= 1000) {
                    $score2 = 98;
                } elseif ($A_PROGROL >= 1000.1 && $A_PROGROL <= 10000) {
                    $score2 = 99;
                } elseif ($A_PROGROL >= 10000.1) {
                    $score2 = 99.5;
                } else {
                    $score2 = null;
                }

                $r['year'] = $year;
                $r['val'] = ['DEBTL' => $DEBTL, 'A_PROGROL' => $A_PROGROL];
                $r['score'] = intval($score1 * 0.6 + $score2 * 0.4);
                break;
            }
        }

        return $r;
    }

    //担保能力 (0资产总额 - 1负债总额 - 股权质押接口的出质股权数额 - 动产抵押接口的被担保主债权数额 - 对外担保接口的主债权数额) / 0资产总额
    private function GuaranteeAbility($data, $entName): array
    {
        $r = [
            'name' => '企业担保能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => null
        ];

        $tmp = [
            'gqcz' => [],
            'dcdy' => [],
            'dwdb' => [],
        ];

        //股权出质
        $gqcz = (new LongDunService())->setCheckRespFlag(true)
            ->get($this->ld . 'StockEquityPledge/GetStockPledgeList', [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 100,
            ]);

        if ($gqcz['code'] === 200 && !empty($gqcz['result'])) {
            foreach ($gqcz['result'] as $row) {
                if (!isset($row['PledgedAmount']) || empty(trim($row['PledgedAmount']))) continue;
                if (!isset($row['RegDate']) || !is_numeric(substr($row['RegDate'], 0, 4))) continue;
                preg_match_all('/\d+/', $row['PledgedAmount'], $all);
                $num = current(current($all));
                if (!is_numeric($num)) continue;
                $year = substr($row['RegDate'], 0, 4);
                isset($tmp['gqcz'][$year . 'year']) ?
                    $tmp['gqcz'][$year . 'year'] += $num :
                    $tmp['gqcz'][$year . 'year'] = $num;
            }
        }

        //动产抵押
        $dcdy = (new LongDunService())->setCheckRespFlag(true)
            ->get($this->ld . 'ChattelMortgage/GetChattelMortgage', [
                'keyWord' => $entName,
                'pageIndex' => 1,
                'pageSize' => 100,
            ]);

        if ($dcdy['code'] === 200 && !empty($dcdy['result'])) {
            foreach ($dcdy['result'] as $row) {
                if (!isset($row['DebtSecuredAmount']) || empty(trim($row['DebtSecuredAmount']))) continue;
                if (!isset($row['RegisterDate']) || !is_numeric(substr($row['RegisterDate'], 0, 4))) continue;
                preg_match_all('/\d+/', $row['DebtSecuredAmount'], $all);
                $num = current(current($all));
                if (!is_numeric($num)) continue;
                $year = substr($row['RegisterDate'], 0, 4);
                isset($tmp['dcdy'][$year . 'year']) ?
                    $tmp['dcdy'][$year . 'year'] += $num :
                    $tmp['dcdy'][$year . 'year'] = $num;
            }
        }

        //对外担保
        $dwdb = (new LongDunService())->setCheckRespFlag(true)
            ->get($this->ld . 'AR/GetAnnualReport', [
                'keyNo' => $entName,
            ]);

        if ($dwdb['code'] === 200 && !empty($dwdb['result'])) {
            foreach ($dwdb['result'] as $row) {
                if (!isset($row['ProvideAssuranceList']) || empty($row['ProvideAssuranceList'])) continue;
                preg_match_all('/\d+/', $row['Year'], $all);
                $year = current(current($all));
                if (!is_numeric($year)) continue;
                foreach ($row['ProvideAssuranceList'] as $one) {
                    if (!isset($one['CreditorAmount'])) continue;
                    preg_match_all('/\d+/', $one['CreditorAmount'], $all);
                    $num = current(current($all));
                    if (!is_numeric($num)) continue;
                    isset($tmp['dwdb'][$year . 'year']) ?
                        $tmp['dwdb'][$year . 'year'] += $num :
                        $tmp['dwdb'][$year . 'year'] = $num;
                }
            }
        }

        //实际控制人
        $sjkzr = (new LongDunService())->setCheckRespFlag(true)
            ->get($this->ld . 'Beneficiary/GetBeneficiary', [
                'companyName' => $entName,
                'percent' => 0,
                'mode' => 0,
            ]);

        $temp = [];
        if ($sjkzr['code'] === 200 && !empty($sjkzr['result'])) {
            if (count($sjkzr['result']['BreakThroughList']) > 0) {
                $total = current($sjkzr['result']['BreakThroughList']);
                $total = substr($total['TotalStockPercent'], 0, -1);
                if ($total >= 50) {
                    //如果第一个人就是大股东了，就直接返回
                    $temp = [];
                } else {
                    //把返回的所有人加起来和100做减法，求出坑
                    $hole = 100;
                    foreach ($sjkzr['result']['BreakThroughList'] as $key => $val) {
                        $hole -= substr($val['TotalStockPercent'], 0, -1);
                    }
                    //求出坑的比例，如果比第一个人大，那就是特殊机构，如果没第一个人大，那第一个人就是控制人
                    if ($total > $hole) $temp = $sjkzr['result']['BreakThroughList'][0];
                }
            }
        }

        //实际控制人算不算进权重
        !empty($temp) ? $type = true : $type = false;

        foreach ($data as $year => $arr) {
            $arr['ASSGRO'] != 0 ?: $arr['ASSGRO'] = 0.01;
            if (is_numeric($arr['ASSGRO']) && is_numeric($arr['LIAGRO']) && $arr['ASSGRO'] != 0) {
                isset($tmp['gqcz'][$year . 'year']) ? $gqcz = $tmp['gqcz'][$year . 'year'] : $gqcz = 0;
                isset($tmp['dcdy'][$year . 'year']) ? $dcdy = $tmp['dcdy'][$year . 'year'] : $dcdy = 0;
                isset($tmp['dwdb'][$year . 'year']) ? $dwdb = $tmp['dwdb'][$year . 'year'] : $dwdb = 0;
                $val = ($arr['ASSGRO'] - $arr['LIAGRO'] - $gqcz - $dcdy - $dwdb) / $arr['ASSGRO'] * 100;
                if ($val <= -10) {
                    $score = 4;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 8;
                } elseif ($val >= -5 && $val <= -1) {
                    $score = 11;
                } elseif ($val >= -1 && $val <= 0) {
                    $score = 16;
                } elseif ($val >= 0 && $val <= 5) {
                    $score = 26;
                } elseif ($val >= 5.1 && $val <= 10) {
                    $score = 31;
                } elseif ($val >= 10.1 && $val <= 30) {
                    $score = 35;
                } elseif ($val >= 30.1 && $val <= 40) {
                    $score = 42;
                } elseif ($val >= 40.1 && $val <= 50) {
                    $score = 66;
                } elseif ($val >= 50.1 && $val <= 60) {
                    $score = 78.5;
                } elseif ($val >= 60.1 && $val <= 70) {
                    $score = 85;
                } elseif ($val >= 70.1 && $val <= 80) {
                    $score = 92.5;
                } elseif ($val >= 80.1 && $val <= 90) {
                    $score = 97.5;
                } elseif ($val >= 90) {
                    $score = 99;
                } else {
                    $score = null;
                }
                ($type && $score !== null) ? $score = intval($score * 0.7 + 100 * 0.3) : null;
                $r['year'] = $year;
                $r['val'] = $val;
                $r['score'] = intval($score * 0.8);
                break;
            }
        }

        return $r;
    }
}
