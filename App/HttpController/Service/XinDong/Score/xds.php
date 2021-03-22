<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\Common\CommonService;
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
}
