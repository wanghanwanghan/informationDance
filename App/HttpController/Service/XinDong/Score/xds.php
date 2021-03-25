<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\QiChaCha\QiChaChaService;

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

    function __construct()
    {
        $this->qcc = CreateConf::getInstance()->getConf('qichacha.baseUrl');
    }

    function cwScore($entName): ?array
    {
        $arr = (new LongXinService())->setCheckRespFlag(true)->getFinanceData([
            'entName' => $entName,
            'code' => '',
            'beginYear' => date('Y') - 1,
            'dataCount' => 4,
        ], false);

        if (!isset($arr['code']) || !isset($arr['result']) || $arr['code'] !== 200 || empty($arr['result'])) {
            return null;
        }

        //年份转string
        $tmp = [];
        foreach ($arr['result'] as $year => $val) {
            $tmp[$year . ''] = $val;
        }
        $arr['result'] = $tmp;

        $score = [];

        //企业资产收益评分 总资产收益率 = 5净利润 / 10平均资产总额
        $score['ASSGROPROFIT_REL'] = $this->ASSGROPROFIT_REL($arr['result']);

        //企业资产负债状况评分 20资产负债率 = 1负债总额 / 0资产总额
        $score['DEBTL'] = $this->DEBTL($arr['result']);

        //企业盈利能力评分 4利润总额
        $score['PROGRO'] = $this->PROGRO($arr['result']);

        //企业营收增长能力评分 33主营业务收入同比 MAIBUSINC_yoy
        $score['MAIBUSINC_yoy'] = $this->MAIBUSINC_yoy($arr['result']);

        //企业利润增长能力评分 34利润总额同比 PROGRO_yoy
        $score['PROGRO_yoy'] = $this->PROGRO_yoy($arr['result']);

        //企业主营业务健康度评分 20资产负债率 = 1负债总额 / 0资产总额
        $score['DEBTL_H'] = $this->DEBTL($arr['result']);

        //企业资本保值状况评分 7期末所有者权益 / 7期初所有者权益 TOTEQU
        $score['TOTEQU'] = $this->TOTEQU($arr['result']);

        //企业人均产能评分 3主营业务收入 / 8缴纳社保人数人均
        $score['PERCAPITA_C'] = $this->PERCAPITA_C($arr['result']);

        //企业人均盈利能力评分 5净利润 / 8缴纳社保人数
        $score['PERCAPITA_Y'] = $this->PERCAPITA_Y($arr['result']);

        //企业纳税能力综合评分 6纳税总额
        $score['RATGRO'] = $this->RATGRO($arr['result']);

        //资产回报能力 5净利润 / 11平均净资产
        $score['ASSETS'] = $this->ASSETS($arr['result']);

        //资产周转能力 2营业收入 / 10平均资产总额
        $score['ATOL'] = $this->ATOL($arr['result']);

        //总资产增长状况 30资产总额同比 ASSGRO_yoy
        $score['ASSGRO_yoy'] = $this->ASSGRO_yoy($arr['result']);

        //税负强度 28税收负担率 TBR
        $score['TBR'] = $this->TBR($arr['result']);

        //还款能力 20资产负债率 DEBTL 60% && 16企业人均盈利 A_PROGROL 40%
        $score['RepaymentAbility'] = $this->RepaymentAbility($arr['result']);

        //担保能力 (0资产总额 - 1负债总额 - 股权质押接口的出质股权数额 - 动产抵押接口的被担保主债权数额 - 对外担保接口的主债权数额) / 0资产总额
        $score['GuaranteeAbility'] = $this->GuaranteeAbility($arr['result'], $entName);

        return $score;
    }

    //企业资产收益评分 总资产收益率 = 5净利润 / 10平均资产总额
    private function ASSGROPROFIT_REL($data): array
    {
        $r = [
            'name' => '企业资产收益评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['A_ASSGROL'])) {
                if ($arr['NETINC'] > 0 && $arr['A_ASSGROL'] > 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['NETINC'] / $arr['A_ASSGROL'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
                    break;
                }
            }
        }

        return $r;
    }

    //企业资产负债状况评分 20资产负债率 = 1负债总额 / 0资产总额
    private function DEBTL($data): array
    {
        $r = [
            'name' => '企业资产负债状况评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['LIAGRO']) && is_numeric($arr['ASSGRO'])) {
                if ($arr['LIAGRO'] > 0 && $arr['ASSGRO'] > 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['LIAGRO'] / $arr['ASSGRO'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
                    break;
                }
            }
        }

        return $r;
    }

    //企业盈利能力评分 4利润总额
    private function PROGRO($data): array
    {
        $r = [
            'name' => '企业盈利能力评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        //值为负但同比为正的则分数为20分起
        //值为负则分数为10分
        //值为正，每多100万加10分，最多50分，
        //再50分往上的利润金额余额则除以每1000万加10分，最多70分，
        //再70分往上的余额则除以每1亿加10分，最多100分

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['PROGRO'])) {
                if ($arr['PROGRO'] < 0 && is_numeric($arr['PROGRO_yoy']) && $arr['PROGRO_yoy'] > 0) {
                    $score = 20;
                } else {
                    $score = 10;
                }
                if ($arr['PROGRO'] > 0) {
                    //有多少个100万
                    $bai = current(explode('.', round($arr['PROGRO'] / 100)));
                    if ($bai >= 1) {
                        if ($bai * 10 > 50) {
                            $arr['PROGRO'] -= 500;
                        }
                        $score = $bai * 10 > 50 ? 50 : $bai * 10;
                    }

                    //有多少个1000万
                    $qian = current(explode('.', round($arr['PROGRO'] / 1000)));
                    if ($qian >= 1) {
                        if ($qian * 10 > 20) {
                            $arr['PROGRO'] -= 2000;
                        }
                        $score = $qian * 10 > 20 ? 70 : $qian * 10 + 50;
                    }

                    //有多少个10000万 1亿
                    $yi = current(explode('.', round($arr['PROGRO'] / 10000)));
                    if ($yi >= 1) {
                        if ($yi * 10 > 30) {
                            $arr['PROGRO'] -= 30000;
                        }
                        $score = $yi * 10 > 30 ? 100 : $yi * 10 + 70;
                    }
                }

                $r['year'] = $year;
                $r['val'] = $arr['PROGRO'];
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
            'name' => '企业营收增长能力评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        //用0%-100%，负增长均为1~9分，1~10%为11~20分，11%~100%为每多9%加10分，最多100分

        foreach ($data as $year => $arr) {
            $score = 1;
            if (is_numeric($arr['MAIBUSINC_yoy'])) {
                $num = floor($arr['MAIBUSINC_yoy'] * 100);
                if ($num < 0) {
                    if ($num < -100) {
                        $score = 1;
                    } else {
                        if ($num >= -100 && $num < -80) {
                            $score = 1;
                        } elseif ($num >= -80 && $num < -60) {
                            $score = 2;
                        } elseif ($num >= -60 && $num < -40) {
                            $score = 3;
                        } elseif ($num >= -40 && $num < -20) {
                            $score = 4;
                        } else {
                            $score = 5;
                        }
                    }
                }
                if ($num === 0) {
                    $score = 1;
                }
                if ($num > 0) {
                    if ($num >= 1 && $num <= 10) {
                        $tmp = [6, 7.5, 9, 10.5, 12, 14, 15.5, 17, 18.5, 20];
                        $score = $tmp[$num - 1];
                    }
                    if ($num >= 11 && $num <= 100) {
                        $score = floor($num / 9) * 10 > 100 ? 100 : floor($num / 9) * 10;
                    }
                    if ($num > 100) {
                        $score = 100;
                    }
                }
                $r['year'] = $year;
                $r['val'] = $arr['MAIBUSINC_yoy'];
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
            'name' => '企业利润增长能力评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        //用0%-100%，负增长均为1~9分，1~10%为11~20分，11%~100%为每多9%加10分，最多100分

        foreach ($data as $year => $arr) {
            $score = 1;
            if (is_numeric($arr['PROGRO_yoy'])) {
                $num = floor($arr['PROGRO_yoy'] * 100);
                if ($num < 0) {
                    $score = 10 - substr($num, 0, 1);
                }
                if ($num === 0) {
                    $score = 1;
                }
                if ($num > 0) {
                    if ($num >= 1 && $num <= 10) {
                        $score = $num;
                    }
                    if ($num >= 11 && $num <= 100) {
                        $score = floor($num / 9) * 10 > 100 ? 100 : floor($num / 9) * 10;
                    }
                    if ($num > 100) {
                        $score = 100;
                    }
                }
                $r['year'] = $year;
                $r['val'] = $arr['PROGRO_yoy'];
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
            'name' => '企业资本保值状况评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['TOTEQU']) && is_numeric($data[($year - 1) . '']['TOTEQU'])) {
                if ($data[($year - 1) . '']['TOTEQU'] !== 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['TOTEQU'] / $data[($year - 1) . '']['TOTEQU'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
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
            'name' => '企业人均产能评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['MAIBUSINC']) && is_numeric($arr['SOCNUM'])) {
                if ($arr['SOCNUM'] > 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['MAIBUSINC'] / $arr['SOCNUM'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
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
            'name' => '企业人均盈利能力评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['SOCNUM'])) {
                if ($arr['SOCNUM'] > 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['NETINC'] / $arr['SOCNUM'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
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
            'name' => '企业纳税能力综合评分',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 10
        ];

        //值为0或无则为10分，3万10分，每多10万加10分，最多60分，
        //再60分往上的纳税金额余额（指减掉60分对应金额后的金额）则除以每100万加10分，最多80分，
        //再80分往上的余额则除以每1千万加10分，最多100分

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['RATGRO'])) {
                if ($arr['RATGRO'] > 0 && $arr['RATGRO'] <= 3) {
                    $score = 10;
                }
                if ($arr['RATGRO'] > 3) {
                    //有多少个10万
                    $shi = current(explode('.', round($arr['RATGRO'] / 10)));
                    if ($shi >= 1) {
                        if ($shi * 10 > 60) {
                            $arr['RATGRO'] -= 60;
                        }
                        $score = $shi * 10 > 60 ? 60 : $shi * 10;
                    }

                    //有多少个100万
                    $bai = current(explode('.', round($arr['RATGRO'] / 100)));
                    if ($bai >= 1) {
                        if ($bai * 10 > 20) {
                            $arr['RATGRO'] -= 200;
                        }
                        $score = $bai * 10 > 20 ? 80 : $bai * 10 + 60;
                    }

                    //有多少个1000万
                    $qian = current(explode('.', round($arr['RATGRO'] / 1000)));
                    if ($qian >= 1) {
                        $score = $qian * 10 > 20 ? 100 : $qian * 10 + 80;
                    }
                }

                $r['year'] = $year;
                $r['val'] = $arr['RATGRO'];
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
            'name' => '资产回报能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        //用“负比例~0%-100%”，负增长均为1~5分，1~10%为6~20分，11%~100%为每多9%加20分，最多100分

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['NETINC']) && is_numeric($arr['CA_ASSGRO'])) {
                if ($arr['CA_ASSGRO'] !== 0) {
                    $num = round($arr['NETINC'] / $arr['CA_ASSGRO']) * 100;
                    $num = substr($num, 0, strpos($num, '.'));
                    if ($num < 0) {
                        if ($num < -100) {
                            $score = 1;
                        } else {
                            if ($num >= -100 && $num < -80) {
                                $score = 1;
                            } elseif ($num >= -80 && $num < -60) {
                                $score = 2;
                            } elseif ($num >= -60 && $num < -40) {
                                $score = 3;
                            } elseif ($num >= -40 && $num < -20) {
                                $score = 4;
                            } else {
                                $score = 5;
                            }
                        }
                    }
                    if ($num === 0) {
                        $score = 1;
                    }
                    if ($num > 0) {
                        if ($num >= 1 && $num <= 10) {
                            $tmp = [6, 7.5, 9, 10.5, 12, 14, 15.5, 17, 18.5, 20];
                            $score = $tmp[$num - 1];
                        }
                        if ($num >= 11 && $num <= 100) {
                            $score = floor($num / 9) * 20 + 20 > 100 ? 100 : floor($num / 9) * 20 + 20;
                        }
                        if ($num > 100) {
                            $score = 100;
                        }
                    }
                    $r['year'] = $year;
                    $r['val'] = $arr['NETINC'] / $arr['CA_ASSGRO'];
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
            'name' => '资产周转能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['VENDINC']) && is_numeric($arr['A_ASSGROL'])) {
                if ($arr['A_ASSGROL'] > 0) {
                    $r['year'] = $year;
                    $r['val'] = $arr['VENDINC'] / $arr['A_ASSGROL'];
                    $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                    $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
                    break;
                }
            }
        }

        return $r;
    }

    //总资产增长状况 30资产总额同比 ASSGRO_yoy
    private function ASSGRO_yoy($data): array
    {
        $r = [
            'name' => '总资产增长状况',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        //用0%-100%，负增长均为1~9分，1~10%为11~20分，11%~100%为每多9%加10分，最多100分

        foreach ($data as $year => $arr) {
            $score = 1;
            if (is_numeric($arr['ASSGRO_yoy'])) {
                $num = floor($arr['ASSGRO_yoy'] * 100);
                if ($num < 0) {
                    if ($num < -100) {
                        $score = 1;
                    } else {
                        if ($num >= -100 && $num < -80) {
                            $score = 1;
                        } elseif ($num >= -80 && $num < -60) {
                            $score = 2;
                        } elseif ($num >= -60 && $num < -40) {
                            $score = 3;
                        } elseif ($num >= -40 && $num < -20) {
                            $score = 4;
                        } else {
                            $score = 5;
                        }
                    }
                }
                if ($num === 0) {
                    $score = 1;
                }
                if ($num > 0) {
                    if ($num >= 1 && $num <= 10) {
                        $tmp = [6, 7.5, 9, 10.5, 12, 14, 15.5, 17, 18.5, 20];
                        $score = $tmp[$num - 1];
                    }
                    if ($num >= 11 && $num <= 100) {
                        $score = floor($num / 9) * 10 + 20 > 100 ? 100 : floor($num / 9) * 10 + 20;
                    }
                    if ($num > 100) {
                        $score = 100;
                    }
                }
                $r['year'] = $year;
                $r['val'] = $arr['ASSGRO_yoy'];
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
            'name' => '税负强度',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['TBR'])) {
                $r['year'] = $year;
                $r['val'] = $arr['TBR'];
                $r['score'] = current(explode('.', round($r['val'] * 100))) - 0;
                $r['score'] = $r['score'] > 1 ? $r['score'] : 1;
                break;
            }
        }

        return $r;
    }

    //还款能力 20资产负债率 DEBTL 60% && 16企业人均盈利 A_PROGROL 40%
    private function RepaymentAbility($data): array
    {
        $r = [
            'name' => '还款能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['DEBTL']) && is_numeric($arr['A_PROGROL'])) {
                $DEBTL = floor($arr['DEBTL'] * 100);
                $A_PROGROL = floor($arr['A_PROGROL']);

                if ($DEBTL >= 0 && $A_PROGROL >= 0) {
                    $DEBTL_score = (100 - $DEBTL) * 0.6;

                    if ($A_PROGROL > 1 && $A_PROGROL <= 5) {
                        $A_PROGROL_score = 20;
                    } else if ($A_PROGROL > 5 && $A_PROGROL <= 8) {
                        $A_PROGROL_score = 30;
                    } else if ($A_PROGROL > 8 && $A_PROGROL <= 10) {
                        $A_PROGROL_score = 40;
                    } else if ($A_PROGROL > 10 && $A_PROGROL <= 15) {
                        $A_PROGROL_score = 50;
                    } else if ($A_PROGROL > 15 && $A_PROGROL <= 20) {
                        $A_PROGROL_score = 60;
                    } else if ($A_PROGROL > 20 && $A_PROGROL <= 60) {
                        $A_PROGROL_score = 70;
                    } else if ($A_PROGROL > 60 && $A_PROGROL <= 100) {
                        $A_PROGROL_score = 80;
                    } else if ($A_PROGROL > 100 && $A_PROGROL <= 500) {
                        $A_PROGROL_score = 90;
                    } else if ($A_PROGROL > 500) {
                        $A_PROGROL_score = 100;
                    } else {
                        $A_PROGROL_score = 10;
                    }

                    $A_PROGROL_score = $A_PROGROL_score * 0.4;

                    $r['year'] = $year;
                    $r['val'] = ['DEBTL' => $DEBTL, 'A_PROGROL' => $A_PROGROL];
                    $r['score'] = round($DEBTL_score + $A_PROGROL_score);
                    break;
                }
            }
        }

        return $r;
    }

    //担保能力 (0资产总额 - 1负债总额 - 股权质押接口的出质股权数额 - 动产抵押接口的被担保主债权数额 - 对外担保接口的主债权数额) / 0资产总额
    private function GuaranteeAbility($data, $entName): array
    {
        $r = [
            'name' => '担保能力',
            'field' => __FUNCTION__,
            'year' => null,
            'val' => null,
            'score' => 1
        ];

        $tmp = [
            'gqcz' => [],
            'dcdy' => [],
        ];

        //股权出质
        $gqcz = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qcc . 'StockEquityPledge/GetStockPledgeList', [
                'searchKey' => $entName,
                'pageIndex' => 1,
                'pageSize' => 100,
            ]);

        if ($gqcz['code'] === 200 && !empty($gqcz['result'])) {
            foreach ($gqcz['result'] as $row) {
                if (!isset($row['PledgedAmount']) || !is_numeric($row['PledgedAmount'])) continue;
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
        $dcdy = (new QiChaChaService())->setCheckRespFlag(true)
            ->get($this->qcc . 'ChattelMortgage/GetChattelMortgage', [
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


        CommonService::getInstance()->log4PHP([$tmp, $gqcz, $dcdy]);

        foreach ($data as $year => $arr) {
            if (is_numeric($arr['DEBTL']) && is_numeric($arr['A_PROGROL'])) {
                $r['year'] = $year;
                $r['val'] = 1;
                $r['score'] = 1;
                break;
            }
        }

        return $r;
    }
}
