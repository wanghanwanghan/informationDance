<?php

namespace App\HttpController\Service\XinDong\Score;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaYanYuan\FaYanYuanService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\TaoShu\TaoShuService;

class FenShuService extends ServiceBase
{
//获取企业的风险分
    public function getFengXian($entName)
    {
        //企业变更信息
        $res = (new TaoShuService())->setCheckRespFlag(true)->post(
            [
                'entName'  => $entName,
                'pageNo'  => 1,
                'pageSize' => 10,
            ], 'getRegisterChangeInfo');

        ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

        $getRegisterChangeInfo['list']  = $res;
        $getRegisterChangeInfo['total'] = $total;

        //龙盾 经营异常
        $postData = ['keyNo' => $entName];
        $res      = (new LongDunService())
            ->setCheckRespFlag(true)
            ->get($this->ldUrl . 'ECIException/GetOpException', $postData);

        ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

        $getOpException['list']  = $res;
        $getOpException['total'] = $total;

        $a = $this->qybgxx($getRegisterChangeInfo['total']);
        //经营异常
        $b = $this->jyyc($getOpException['total']);
        //计算
        $fx['gongshang'] = (0.6 * $a + 0.4 * $b) * 0.05;
        //==============================================================================================================
        //财务资产
        $financeData = $this->getCaiWu($entName);
        $d           = $this->cwzc($financeData['data'], 'fx');
        //计算
        $fx['caiwu'] = ($d[0] * 0.5 + $d[1] * 0.5) * 0.6;
        //==============================================================================================================
        //近三年团队人数
        $a = $this->tdrs(0, 'fx');
        //近两年团队人数
        $b = $this->rybh(0, 'fx');
        //计算
        $fx['tuandui'] = (0.3 * $a + 0.7 * $b) * 0.18;
        //==============================================================================================================
        //裁判文书
        $cpws = $this->getPjws($entName);
        $a    = $this->pjws($cpws['total']);
        //执行公告
        $zxgg = $this->getZxgg($entName);
        $b    = $this->zxgg($zxgg['total']);
        $s    = ($a + $b) / 2;
        //计算
        $fx['sifa'] = 0.25 * $s;
        CommonService::getInstance()->log4PHP($fx,'info','getFengXian');
        return $this->checkResp(200, null, $fx, '成功');
//        return $fx;

    }

    private function getZxgg($entName)
    {
        $fyyList   = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $fyyDetail = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');


        $doc_type = 'zxgg';

        $postData = [
            'doc_type' => $doc_type,
            'keyword'  => $entName,
            'pageno'   => 1,
            'range'    => 20,
        ];

        $res = (new FaYanYuanService())->setCheckRespFlag(true)->getList($fyyList . 'sifa', $postData);

        ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

        if (!empty($res)) {
            foreach ($res as &$one) {
                //取详情
                $postData = [
                    'id'       => $one['entryId'],
                    'doc_type' => $doc_type
                ];

                $detail = (new FaYanYuanService())->setCheckRespFlag(true)->getDetail($fyyDetail . $doc_type, $postData);

                if ($detail['code'] === 200 && !empty($detail['result'])) {
                    $one['detail'] = current($detail['result']);
                } else {
                    $one['detail'] = null;
                }
            }
            unset($one);
        }

        $tmp['list']  = $res;
        $tmp['total'] = $total;

        return $tmp;
    }

    private function getPjws($entName)
    {

        $fyyList   = CreateConf::getInstance()->getConf('fayanyuan.listBaseUrl');
        $fyyDetail = CreateConf::getInstance()->getConf('fayanyuan.detailBaseUrl');

        $doc_type = 'cpws';

        $postData = [
            'doc_type' => $doc_type,
            'keyword'  => $entName,
            'pageno'   => 1,
            'range'    => 20,
        ];

        $res = (new FaYanYuanService())->setCheckRespFlag(true)->getList($fyyList . 'sifa', $postData);

        ($res['code'] === 200 && !empty($res['result'])) ? list($res, $total) = [$res['result'], $res['paging']['total']] : list($res, $total) = [null, null];

        if (!empty($res)) {
            foreach ($res as &$one) {
                //取详情
                $postData = [
                    'id'       => $one['entryId'],
                    'doc_type' => $doc_type
                ];

                $detail = (new FaYanYuanService())->setCheckRespFlag(true)->getDetail($fyyDetail . $doc_type, $postData);

                if ($detail['code'] === 200 && !empty($detail['result'])) {
                    $one['detail'] = current($detail['result']);
                } else {
                    $one['detail'] = null;
                }
            }
            unset($one);
        }

        $tmp['list']  = $res;
        $tmp['total'] = $total;

        return $tmp;
    }

    private function getCaiWu($entName)
    {


        $postData = [
            'entName'   => $entName,
            'code'    => '',
            'beginYear' => date('Y') - 1,
            'dataCount' => 4,//取最近几年的
        ];

        $res = (new LongXinService())->setCheckRespFlag(true)->getFinanceData($postData, false);

        if ($res['code'] !== 200) return '';

        ksort($res['result']);

        if (!empty($res['result'])) {
            $tmp = $lineTemp = $legend = [];
            foreach ($res['result'] as $year => $val) {
                $legend[]                    = $year;
                $tmp[]    = [
                    sRound($val['ASSGRO_yoy'] * 100),
                    sRound($val['LIAGRO_yoy'] * 100),
                    sRound($val['VENDINC_yoy'] * 100),
                    sRound($val['MAIBUSINC_yoy'] * 100),
                    sRound($val['PROGRO_yoy'] * 100),
                    sRound($val['NETINC_yoy'] * 100),
                    sRound($val['RATGRO_yoy'] * 100),
                    sRound($val['TOTEQU_yoy'] * 100),
                ];
                $lineTemp['MAIBUSINC_yoy'][] = sRound($val['MAIBUSINC_yoy'] * 100);//主营业务收入
                $lineTemp['PROGRO_yoy'][]    = sRound($val['PROGRO_yoy'] * 100);   //利润总额
                $lineTemp['ASSGRO_yoy'][]    = sRound($val['ASSGRO_yoy'] * 100);   //资产总额
                $lineTemp['RATGRO_yoy'][]    = sRound($val['RATGRO_yoy'] * 100);   //纳税总额
                $lineTemp['LIAGRO_yoy'][]    = sRound($val['LIAGRO_yoy'] * 100);   //负债总额
            }
            $res['data']   = $res['result'];
            $res['result'] = $tmp;
        }

        $labels = ['资产总额', '负债总额', '营业总收入', '主营业务收入', '利润总额', '净利润', '纳税总额', '所有者权益'];

        $extension = [
            'width'     => 1200,
            'height' => 700,
            'title'  => $entName . ' - 同比',
            'xTitle' => '此图为概况信息',
            //'yTitle'=>$this->entName,
            'titleSize' => 14,
            'legend'    => $legend
        ];

        $tmp = [];
        //$tmp['pic'] = CommonService::getInstance()->createBarPic($res['result'], $labels, $extension);
        $tmp['pic'][] = CommonService::getInstance()->createLinePic($lineTemp['MAIBUSINC_yoy'], $legend, [
            'title'    => $entName,
            'subTitle' => '营收规模同比 此图为概况信息',
        ]);
        $tmp['pic'][] = CommonService::getInstance()->createLinePic($lineTemp['PROGRO_yoy'], $legend, [
            'title'    => $entName,
            'subTitle' => '盈利能力同比 此图为概况信息',
        ]);
        $tmp['pic'][] = CommonService::getInstance()->createLinePic($lineTemp['ASSGRO_yoy'], $legend, [
            'title'    => $entName,
            'subTitle' => '资产规模同比 此图为概况信息',
        ]);
        $tmp['pic'][] = CommonService::getInstance()->createLinePic($lineTemp['RATGRO_yoy'], $legend, [
            'title'    => $entName,
            'subTitle' => '纳税能力同比 此图为概况信息',
        ]);
        $tmp['pic'][] = CommonService::getInstance()->createLinePic($lineTemp['LIAGRO_yoy'], $legend, [
            'title'    => $entName,
            'subTitle' => '负债规模同比 此图为概况信息',
        ]);
        $tmp['data']  = $res['data'];
        return $tmp;
    }

    //企业变更信息
    private function qybgxx($data)
    {
        $num = (int)$data;

        //算分
        if ($num > 10) return 100;
        if ($num <= 10 && $num >= 6) return 80;
        if ($num <= 5 && $num >= 3) return 70;
        if ($num <= 2 && $num >= 1) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //财务资产
    private function cwzc($data, $type): array
    {
        if (!is_array($data)) return [0, 0];

        if (empty($data)) return [0, 0];

        $data = array_values($data);

        if (!isset($data[0])) return [0, 0];

        if ($type === 'fz') {
            if (is_numeric($data[0]['NETINC']) && is_numeric($data[0]['A_ASSGROL'])) {
                $data[0]['A_ASSGROL'] == 0 ? $now = false : $now = round($data[0]['NETINC'] / $data[0]['A_ASSGROL'], 6);
            } else {
                $now = false;
            }
            if (is_numeric($data[1]['NETINC']) && is_numeric($data[1]['A_ASSGROL'])) {
                $data[1]['A_ASSGROL'] == 0 ? $last = false : $last = round($data[1]['NETINC'] / $data[1]['A_ASSGROL'], 6);
            } else {
                $last = false;
            }
            if ($now === false || $last === false) {
                $score = 4;
            } else {
                $val = round((($now - $last) / abs($last)) * 100);
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
                    $score = 4;
                }
            }
        } else {
            if (is_numeric($data[0]['PROGRO_yoy'])) {
                $val = round($data[0]['PROGRO_yoy'] * 100);
                if ($val <= -50) {
                    $score = 97;
                } elseif ($val >= -50 && $val <= -21) {
                    $score = 94;
                } elseif ($val >= -20 && $val <= -11) {
                    $score = 92;
                } elseif ($val >= -10 && $val <= -6) {
                    $score = 85;
                } elseif ($val >= -5 && $val <= 0) {
                    $score = 72;
                } elseif ($val >= 0 && $val <= 5) {
                    $score = 56;
                } elseif ($val >= 6 && $val <= 10) {
                    $score = 42;
                } elseif ($val >= 11 && $val <= 25) {
                    $score = 35;
                } elseif ($val >= 26 && $val <= 30) {
                    $score = 31;
                } elseif ($val >= 31 && $val <= 50) {
                    $score = 26;
                } elseif ($val >= 51 && $val <= 70) {
                    $score = 21;
                } elseif ($val >= 71 && $val <= 100) {
                    $score = 16;
                } elseif ($val >= 101 && $val <= 200) {
                    $score = 11;
                } elseif ($val >= 201 && $val <= 500) {
                    $score = 8;
                } elseif ($val >= 500) {
                    $score = 4;
                } else {
                    $score = 97;
                }
            } else {
                $score = 97;
            }
        }

        switch ($type) {
            case 'fz':
                //营业收入
                $vendInc = $data[0]['VENDINC'];
                if ($vendInc > 20) $vendIncNum = 110;
                if ($vendInc > 10 && $vendInc <= 20) $vendIncNum = 100;
                if ($vendInc > 5 && $vendInc <= 10) $vendIncNum = 90;
                if ($vendInc >= 0 && $vendInc <= 5) $vendIncNum = 80;
                if ($vendInc >= -10 && $vendInc <= -1) $vendIncNum = 70;
                if ($vendInc >= -20 && $vendInc <= -11) $vendIncNum = 60;
                if ($vendInc <= -21) $vendIncNum = 50;
                //净利润
                $netInc = $data[0]['NETINC'];
                if ($netInc > 20) $netIncNum = 110;
                if ($netInc > 10 && $netInc <= 20) $netIncNum = 100;
                if ($netInc > 5 && $netInc <= 10) $netIncNum = 90;
                if ($netInc >= 0 && $netInc <= 5) $netIncNum = 80;
                if ($netInc >= -10 && $netInc <= -1) $netIncNum = 70;
                if ($netInc >= -20 && $netInc <= -11) $netIncNum = 60;
                if ($netInc <= -21) $netIncNum = 50;
                //资产总额
                $assGro = $data[0]['ASSGRO'];
                if ($assGro > 20) $assGroNum = 110;
                if ($assGro > 10 && $assGro <= 20) $assGroNum = 100;
                if ($assGro > 5 && $assGro <= 10) $assGroNum = 90;
                if ($assGro >= 0 && $assGro <= 5) $assGroNum = 80;
                if ($assGro >= -10 && $assGro <= -1) $assGroNum = 70;
                if ($assGro >= -20 && $assGro <= -11) $assGroNum = 60;
                if ($assGro <= -21) $assGroNum = 50;
                return [($vendIncNum + $netIncNum + $assGroNum) / 3, $score];
            case 'fx':
                //负债总额/资产总额=资产负债率
                if (count($data) < 2) return [0, 0];
                //今年负债总额
                $liaGro1 = $data[0]['LIAGRO'];
                //今年资产总额
                $assGro1 = $data[0]['ASSGRO'];
                //今年资产负债率
                if ($assGro1 == 0) {
                    $fuzhailv1 = 0;
                } else {
                    $fuzhailv1 = ($liaGro1 / $assGro1) * 100;
                }
                //去年负债总额
                $liaGro2 = $data[1]['LIAGRO'];
                //去年资产总额
                $assGro2 = $data[1]['ASSGRO'];
                //今年资产负债率
                if ($assGro2 == 0) {
                    $fuzhailv2 = 0;
                } else {
                    $fuzhailv2 = ($liaGro2 / $assGro2) * 100;
                }
                $num = (abs($fuzhailv1) + abs($fuzhailv2)) / 2;
                if ($num > 80) return [100, $score];
                if ($num > 50 && $num <= 80) return [90, $score];
                if ($num > 30 && $num <= 50) return [80, $score];
                if ($num > 10 && $num <= 30) return [70, $score];
                if ($num > 0 && $num <= 10) return [60, $score];
                break;
        }

        return [0, 0];
    }

    //经营异常
    private function jyyc($data)
    {
        //总数
        $num = (int)$data;

        //算分
        if ($num > 5) return 100;
        if ($num == 4) return 80;
        if ($num == 3) return 70;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //近三年团队人数
    private function tdrs($data, $type)
    {
        return 50;
    }

    //近两年团队人数
    private function rybh($data, $type)
    {
        return 40;
    }

    //判决文书
    private function pjws($data)
    {
        $num = (int)$data;

        //总数
        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //执行公告
    private function zxgg($data)
    {
        $num = (int)$data;

        if ($num > 10) return 100;
        if ($num >= 6 && $num <= 10) return 90;
        if ($num >= 3 && $num <= 5) return 80;
        if ($num >= 1 && $num <= 2) return 60;
        if ($num < 1) return 0;

        return 0;
    }

    //处理结果给信息controller
    private function checkResp($code, $paging, $result, $msg)
    {
        return $this->createReturn((int)$code, $paging, $result, $msg);
    }
}