<?php

namespace App\HttpController\Business\Api\LongXin;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class LongXinController extends LongXinBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验返回值，并给客户计费
    private function checkResponse($res, $ext = [])
    {
        $res['Paging'] = null;

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['Result'] = $res['data'];
        $res['Message'] = $res['msg'];

        $charge = ChargeService::getInstance()->LongXin($this->request(), 51);

        if ($charge['code'] != 200) {
            return $this->writeJson((int)$charge['code'], null, null, $charge['msg'], false);
        } else {
            if (isset($ext['refundToWallet']) && $ext['refundToWallet']) {
                $res['code'] = 250;//本次查询不扣费
            }
            return $this->writeJson((int)$res['code'], $res['Paging'], $res['Result'], $res['Message'], false);
        }
    }

    //近n年的财务数据，不需要授权
    function getFinanceNotAuth()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $postData = [
            'entName' => $entName,
            'code' => $code,
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,//取最近几年的
        ];

        $res = (new LongXinService())->getFinanceData($postData, false);

        //30资产总额同比 ASSGRO_yoy
        //31负债总额同比 LIAGRO_yoy
        //32营业总收入同比 VENDINC_yoy
        //33主营业务收入同比 MAIBUSINC_yoy
        //34利润总额同比 PROGRO_yoy
        //35净利润同比 NETINC_yoy
        //36纳税总额同比 RATGRO_yoy
        //37所有者权益同比 TOTEQU_yoy

        if (!empty($res['data'])) {
            $tmp = [];
            foreach ($res['data'] as $year => $val) {
                $tmp[$year]['ASSGRO_yoy'] = round($val['ASSGRO_yoy'] * 100);
                $tmp[$year]['LIAGRO_yoy'] = round($val['LIAGRO_yoy'] * 100);
                $tmp[$year]['VENDINC_yoy'] = round($val['VENDINC_yoy'] * 100);
                $tmp[$year]['MAIBUSINC_yoy'] = round($val['MAIBUSINC_yoy'] * 100);
                $tmp[$year]['PROGRO_yoy'] = round($val['PROGRO_yoy'] * 100);
                $tmp[$year]['NETINC_yoy'] = round($val['NETINC_yoy'] * 100);
                $tmp[$year]['RATGRO_yoy'] = round($val['RATGRO_yoy'] * 100);
                $tmp[$year]['TOTEQU_yoy'] = round($val['TOTEQU_yoy'] * 100);
                if (array_sum($tmp[$year]) === 0.0) {
                    //如果最后是0，说明所有年份数据都是空，本次查询不收费
                    $dataCount--;
                }
            }
            $res['data'] = $tmp;
        }

        $ext = [];
        if ($dataCount === 0) {
            $ext['refundToWallet'] = true;
        }

        return $this->checkResponse($res, $ext);
    }

    //近n年的财务数据，不需要授权
    function getFinanceNotAuthNew()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $entName = explode(',', $entName);
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '公司名称不能是空');
        }
        $code = $this->request()->getRequestParam('code') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $return = [];

        for ($i = 0; $i < count($entName); $i++) {
            $postData = [
                'entName' => $entName[$i],
                'code' => $code,
                'beginYear' => $beginYear,
                'dataCount' => $dataCount,//取最近几年的
            ];
            $res = (new LongXinService())->getFinanceData($postData, false);
            //30资产总额同比 ASSGRO_yoy
            //31负债总额同比 LIAGRO_yoy
            //32营业总收入同比 VENDINC_yoy
            //33主营业务收入同比 MAIBUSINC_yoy
            //34利润总额同比 PROGRO_yoy
            //35净利润同比 NETINC_yoy
            //36纳税总额同比 RATGRO_yoy
            //37所有者权益同比 TOTEQU_yoy
            if (!empty($res['data'])) {
                $tmp = [];
                foreach ($res['data'] as $year => $val) {
                    $tmp[$year]['ASSGRO_yoy'] = round($val['ASSGRO_yoy'] * 100);
                    $tmp[$year]['LIAGRO_yoy'] = round($val['LIAGRO_yoy'] * 100);
                    $tmp[$year]['VENDINC_yoy'] = round($val['VENDINC_yoy'] * 100);
                    $tmp[$year]['MAIBUSINC_yoy'] = round($val['MAIBUSINC_yoy'] * 100);
                    $tmp[$year]['PROGRO_yoy'] = round($val['PROGRO_yoy'] * 100);
                    $tmp[$year]['NETINC_yoy'] = round($val['NETINC_yoy'] * 100);
                    $tmp[$year]['RATGRO_yoy'] = round($val['RATGRO_yoy'] * 100);
                    $tmp[$year]['TOTEQU_yoy'] = round($val['TOTEQU_yoy'] * 100);
                }
                $return[$entName[$i]] = $tmp;
            }
        }

        $ext = [];
        if ($dataCount === 0) {
            $ext['refundToWallet'] = true;
        }

        $res['data'] = $return;

        return $this->checkResponse($res, $ext);
    }

    //近n年的财务数据，需要授权
    function getFinanceNeedAuth()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $postData = [
            'entName' => $entName,
            'code' => $code,
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,//取最近几年的
        ];

        //这里验证授权书是否审核通过
        try {
            $check = AuthBook::create()
                ->where([
                    'phone' => $phone,
                    'entName' => $entName,
                    'status' => 3,
                    'type' => 1,
                ])
                ->where('created_at', Carbon::now()->subYears(1)->timestamp, '>')//1年内有效
                ->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (empty($check))
            return $this->writeJson(201, null, null, '未授权或授权超过1年有效期');

        $res = (new LongXinService())->getFinanceData($postData, false);

        if (!empty($res['data'])) {
            $tmp = [];
            foreach ($res['data'] as $year => $val) {
                $tmp[$year]['ASSGRO'] = round($val['ASSGRO']);
                $tmp[$year]['LIAGRO'] = round($val['LIAGRO']);
                $tmp[$year]['VENDINC'] = round($val['VENDINC']);
                $tmp[$year]['MAIBUSINC'] = round($val['MAIBUSINC']);
                $tmp[$year]['PROGRO'] = round($val['PROGRO']);
                $tmp[$year]['NETINC'] = round($val['NETINC']);
                $tmp[$year]['RATGRO'] = round($val['RATGRO']);
                $tmp[$year]['TOTEQU'] = round($val['TOTEQU']);
                $tmp[$year]['SOCNUM'] = round($val['SOCNUM']);
                if (array_sum($tmp[$year]) === 0.0) {
                    //如果最后是0，说明所有年份数据都是空，本次查询不收费
                    $dataCount--;
                }
            }
            $res['data'] = $tmp;
        }

        $ext = [];
        if ($dataCount === 0) {
            $ext['refundToWallet'] = true;
        }

        return $this->checkResponse($res, $ext);
    }

    //近n年的财务数据，需要授权
    function getFinanceNeedAuthNew()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $entName = explode(',', $entName);
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '公司名称不能是空');
        }
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $return = [];

        for ($i = 0; $i < count($entName); $i++) {
            //这里验证授权书是否审核通过
            try {
                $check = AuthBook::create()
                    ->where([
                        'phone' => $phone,
                        'entName' => $entName[$i],
                        'status' => 3,
                        'type' => 1,
                    ])
                    ->where('created_at', Carbon::now()->subYears(1)->timestamp, '>')//1年内有效
                    ->get();
            } catch (\Throwable $e) {
                return $this->writeErr($e, __FUNCTION__);
            }

            if (empty($check)) {
                return $this->writeJson(201, null, null, '未授权或授权超过1年有效期');
            }

            $postData = [
                'entName' => $entName[$i],
                'code' => $code,
                'beginYear' => $beginYear,
                'dataCount' => $dataCount,//取最近几年的
            ];
            $res = (new LongXinService())->getFinanceData($postData, false);
            //30资产总额同比 ASSGRO_yoy
            //31负债总额同比 LIAGRO_yoy
            //32营业总收入同比 VENDINC_yoy
            //33主营业务收入同比 MAIBUSINC_yoy
            //34利润总额同比 PROGRO_yoy
            //35净利润同比 NETINC_yoy
            //36纳税总额同比 RATGRO_yoy
            //37所有者权益同比 TOTEQU_yoy
            if (!empty($res['data'])) {
                $tmp = [];
                foreach ($res['data'] as $year => $val) {
                    $tmp[$year]['ASSGRO'] = desensitization(intval(round($val['ASSGRO'])));
                    $tmp[$year]['LIAGRO'] = desensitization(intval(round($val['LIAGRO'])));
                    $tmp[$year]['VENDINC'] = desensitization(intval(round($val['VENDINC'])));
                    $tmp[$year]['MAIBUSINC'] = desensitization(intval(round($val['MAIBUSINC'])));
                    $tmp[$year]['PROGRO'] = desensitization(intval(round($val['PROGRO'])));
                    $tmp[$year]['NETINC'] = desensitization(intval(round($val['NETINC'])));
                    $tmp[$year]['RATGRO'] = desensitization(intval(round($val['RATGRO'])));
                    $tmp[$year]['TOTEQU'] = desensitization(intval(round($val['TOTEQU'])));
                    $tmp[$year]['ASSGRO_yoy'] = round($val['ASSGRO_yoy'] * 100);
                    $tmp[$year]['LIAGRO_yoy'] = round($val['LIAGRO_yoy'] * 100);
                    $tmp[$year]['VENDINC_yoy'] = round($val['VENDINC_yoy'] * 100);
                    $tmp[$year]['MAIBUSINC_yoy'] = round($val['MAIBUSINC_yoy'] * 100);
                    $tmp[$year]['PROGRO_yoy'] = round($val['PROGRO_yoy'] * 100);
                    $tmp[$year]['NETINC_yoy'] = round($val['NETINC_yoy'] * 100);
                    $tmp[$year]['RATGRO_yoy'] = round($val['RATGRO_yoy'] * 100);
                    $tmp[$year]['TOTEQU_yoy'] = round($val['TOTEQU_yoy'] * 100);
                }
                $return[$entName[$i]] = $tmp;
            }
        }

        $ext = [];
        if ($dataCount === 0) {
            $ext['refundToWallet'] = true;
        }

        $res['data'] = $return;

        return $this->checkResponse($res, $ext);
    }

    //仿企名片时的财务数据
    function getFinanceTemp()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $entName = explode(',', $entName);
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '公司名称不能是空');
        }
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $code = '';
        $beginYear = 2019;
        $dataCount = 3;
        $ready = [];

        for ($i = 0; $i < count($entName); $i++) {
            $postData = [
                'entName' => $entName[$i],
                'code' => $code,
                'beginYear' => $beginYear,
                'dataCount' => $dataCount,//取最近几年的
            ];
            $res = (new LongXinService())->getFinanceData($postData, false);
            //30资产总额同比 ASSGRO_yoy
            //31负债总额同比 LIAGRO_yoy
            //32营业总收入同比 VENDINC_yoy
            //33主营业务收入同比 MAIBUSINC_yoy
            //34利润总额同比 PROGRO_yoy
            //35净利润同比 NETINC_yoy
            //36纳税总额同比 RATGRO_yoy
            //37所有者权益同比 TOTEQU_yoy
            if (!empty($res['data'])) {
                $tmp = [];
                foreach ($res['data'] as $year => $val) {
                    $tmp[$year]['ASSGRO'] = desensitization(intval(round($val['ASSGRO'])));//资产总额
                    $tmp[$year]['ASSGRO_yoy'] = round($val['ASSGRO_yoy'] * 100);
                    $tmp[$year]['LIAGRO'] = desensitization(intval(round($val['LIAGRO'])));
                    $tmp[$year]['LIAGRO_yoy'] = round($val['LIAGRO_yoy'] * 100);
                    $tmp[$year]['VENDINC'] = desensitization(intval(round($val['VENDINC'])));//营业总收入
                    $tmp[$year]['VENDINC_yoy'] = round($val['VENDINC_yoy'] * 100);
                    $tmp[$year]['MAIBUSINC'] = desensitization(intval(round($val['MAIBUSINC'])));//主营业务收入
                    $tmp[$year]['MAIBUSINC_yoy'] = round($val['MAIBUSINC_yoy'] * 100);
                    $tmp[$year]['PROGRO'] = desensitization(intval(round($val['PROGRO'])));//利润总额
                    $tmp[$year]['PROGRO_yoy'] = round($val['PROGRO_yoy'] * 100);
                    $tmp[$year]['NETINC'] = desensitization(intval(round($val['NETINC'])));
                    $tmp[$year]['NETINC_yoy'] = round($val['NETINC_yoy'] * 100);
                    $tmp[$year]['RATGRO'] = desensitization(intval(round($val['RATGRO'])));
                    $tmp[$year]['RATGRO_yoy'] = round($val['RATGRO_yoy'] * 100);
                    $tmp[$year]['TOTEQU'] = desensitization(intval(round($val['TOTEQU'])));
                    $tmp[$year]['TOTEQU_yoy'] = round($val['TOTEQU_yoy'] * 100);
                    $tmp[$year]['SOCNUM'] = intval(round($val['SOCNUM']));
                    $tmp[$year]['SOCNUM_yoy'] = round($val['SOCNUM_yoy'] * 100);
                }
                $res['data'] = $tmp;
                $ready[$entName[$i]] = $tmp;

                foreach ($ready as $entNameKey => $arr) {
                    //算分
                    $VENDINC = 0;
                    $VENDINC_yoy = 0;
                    $NETINCMAIBUSINC = 0;
                    $PROGRO_yoy = 0;
                    $ASSGRO = 0;
                    $ASSGRO_yoy = 0;
                    foreach ($arr as $yearKey => $fieldArr) {
                        //==========================企业规模状况==========================
                        if ($fieldArr['VENDINC'] <= 0) {
                            $VENDINC_s = 9;
                        } elseif ($fieldArr['VENDINC'] >= 0.01 && $fieldArr['VENDINC'] <= 10) {
                            $VENDINC_s = 10;
                        } elseif ($fieldArr['VENDINC'] >= 10.1 && $fieldArr['VENDINC'] <= 50) {
                            $VENDINC_s = 15;
                        } elseif ($fieldArr['VENDINC'] >= 50.1 && $fieldArr['VENDINC'] <= 300) {
                            $VENDINC_s = 19;
                        } elseif ($fieldArr['VENDINC'] >= 300.1 && $fieldArr['VENDINC'] <= 500) {
                            $VENDINC_s = 23.5;
                        } elseif ($fieldArr['VENDINC'] >= 500.1 && $fieldArr['VENDINC'] <= 1000) {
                            $VENDINC_s = 31.5;
                        } elseif ($fieldArr['VENDINC'] >= 1000.1 && $fieldArr['VENDINC'] <= 5000) {
                            $VENDINC_s = 42.5;
                        } elseif ($fieldArr['VENDINC'] >= 5000.1 && $fieldArr['VENDINC'] <= 10000) {
                            $VENDINC_s = 51;
                        } elseif ($fieldArr['VENDINC'] >= 10000.1 && $fieldArr['VENDINC'] <= 30000) {
                            $VENDINC_s = 62;
                        } elseif ($fieldArr['VENDINC'] >= 30000.1 && $fieldArr['VENDINC'] <= 50000) {
                            $VENDINC_s = 71;
                        } elseif ($fieldArr['VENDINC'] >= 50000.1 && $fieldArr['VENDINC'] <= 100000) {
                            $VENDINC_s = 76;
                        } elseif ($fieldArr['VENDINC'] >= 100000.1 && $fieldArr['VENDINC'] <= 500000) {
                            $VENDINC_s = 82;
                        } elseif ($fieldArr['VENDINC'] >= 500000.1 && $fieldArr['VENDINC'] <= 1000000) {
                            $VENDINC_s = 85;
                        } elseif ($fieldArr['VENDINC'] >= 1000000.1 && $fieldArr['VENDINC'] <= 5000000) {
                            $VENDINC_s = 93;
                        } elseif ($fieldArr['VENDINC'] >= 5000000.1 && $fieldArr['VENDINC'] <= 10000000) {
                            $VENDINC_s = 95;
                        } else {
                            $VENDINC_s = 98;
                        }
                        if ($yearKey == 2017) $VENDINC += $VENDINC_s * 0.1;
                        if ($yearKey == 2018) $VENDINC += $VENDINC_s * 0.3;
                        if ($yearKey == 2019) $VENDINC += $VENDINC_s * 0.6;
                        //==========================企业成长性状况==========================
                        if ($fieldArr['VENDINC_yoy'] <= -50) {
                            $VENDINC_yoy_s = 4;
                        } elseif ($fieldArr['VENDINC_yoy'] >= -50 && $fieldArr['VENDINC_yoy'] <= -21) {
                            $VENDINC_yoy_s = 8;
                        } elseif ($fieldArr['VENDINC_yoy'] >= -20 && $fieldArr['VENDINC_yoy'] <= -11) {
                            $VENDINC_yoy_s = 12;
                        } elseif ($fieldArr['VENDINC_yoy'] >= -10 && $fieldArr['VENDINC_yoy'] <= -6) {
                            $VENDINC_yoy_s = 17;
                        } elseif ($fieldArr['VENDINC_yoy'] >= -5 && $fieldArr['VENDINC_yoy'] <= 0) {
                            $VENDINC_yoy_s = 23;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 0 && $fieldArr['VENDINC_yoy'] <= 5) {
                            $VENDINC_yoy_s = 27;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 6 && $fieldArr['VENDINC_yoy'] <= 10) {
                            $VENDINC_yoy_s = 31.5;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 11 && $fieldArr['VENDINC_yoy'] <= 25) {
                            $VENDINC_yoy_s = 35;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 26 && $fieldArr['VENDINC_yoy'] <= 30) {
                            $VENDINC_yoy_s = 40.5;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 31 && $fieldArr['VENDINC_yoy'] <= 50) {
                            $VENDINC_yoy_s = 54.5;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 51 && $fieldArr['VENDINC_yoy'] <= 70) {
                            $VENDINC_yoy_s = 72;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 71 && $fieldArr['VENDINC_yoy'] <= 100) {
                            $VENDINC_yoy_s = 83.5;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 101 && $fieldArr['VENDINC_yoy'] <= 200) {
                            $VENDINC_yoy_s = 92.5;
                        } elseif ($fieldArr['VENDINC_yoy'] >= 201 && $fieldArr['VENDINC_yoy'] <= 500) {
                            $VENDINC_yoy_s = 95.5;
                        } else {
                            $VENDINC_yoy_s = 97.5;
                        }
                        if ($yearKey == 2017) $VENDINC_yoy += $VENDINC_yoy_s * 0.1;
                        if ($yearKey == 2018) $VENDINC_yoy += $VENDINC_yoy_s * 0.3;
                        if ($yearKey == 2019) $VENDINC_yoy += $VENDINC_yoy_s * 0.6;
                        //==========================企业盈利能力==========================
                        $num = round($fieldArr['NETINC'] / $fieldArr['MAIBUSINC'] * 100);
                        if ($num < 0) {
                            $NETINCMAIBUSINC_s = 10;
                        } elseif ($num >= 1 && $num <= 2) {
                            $NETINCMAIBUSINC_s = 15;
                        } elseif ($num >= 3 && $num <= 5) {
                            $NETINCMAIBUSINC_s = 21;
                        } elseif ($num >= 6 && $num <= 8) {
                            $NETINCMAIBUSINC_s = 30;
                        } elseif ($num >= 9 && $num <= 10) {
                            $NETINCMAIBUSINC_s = 41;
                        } elseif ($num >= 10 && $num <= 100) {
                            $NETINCMAIBUSINC_s = intval($num / 5) * 8;
                        } else {
                            $NETINCMAIBUSINC_s = 97;
                        }
                        if ($yearKey == 2017) $NETINCMAIBUSINC += $NETINCMAIBUSINC_s * 0.1;
                        if ($yearKey == 2018) $NETINCMAIBUSINC += $NETINCMAIBUSINC_s * 0.3;
                        if ($yearKey == 2019) $NETINCMAIBUSINC += $NETINCMAIBUSINC_s * 0.6;
                        //==========================企业盈利可持续能力==========================
                        if ($fieldArr['PROGRO_yoy'] <= -50) {
                            $PROGRO_yoy_s = 4;
                        } elseif ($fieldArr['PROGRO_yoy'] >= -50 && $fieldArr['PROGRO_yoy'] <= -21) {
                            $PROGRO_yoy_s = 8;
                        } elseif ($fieldArr['PROGRO_yoy'] >= -20 && $fieldArr['PROGRO_yoy'] <= -11) {
                            $PROGRO_yoy_s = 11;
                        } elseif ($fieldArr['PROGRO_yoy'] >= -10 && $fieldArr['PROGRO_yoy'] <= -6) {
                            $PROGRO_yoy_s = 16;
                        } elseif ($fieldArr['PROGRO_yoy'] >= -5 && $fieldArr['PROGRO_yoy'] <= 0) {
                            $PROGRO_yoy_s = 21;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 0 && $fieldArr['PROGRO_yoy'] <= 5) {
                            $PROGRO_yoy_s = 26;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 6 && $fieldArr['PROGRO_yoy'] <= 10) {
                            $PROGRO_yoy_s = 31;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 11 && $fieldArr['PROGRO_yoy'] <= 25) {
                            $PROGRO_yoy_s = 35;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 26 && $fieldArr['PROGRO_yoy'] <= 30) {
                            $PROGRO_yoy_s = 42;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 31 && $fieldArr['PROGRO_yoy'] <= 50) {
                            $PROGRO_yoy_s = 56;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 51 && $fieldArr['PROGRO_yoy'] <= 70) {
                            $PROGRO_yoy_s = 72;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 71 && $fieldArr['PROGRO_yoy'] <= 100) {
                            $PROGRO_yoy_s = 85;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 101 && $fieldArr['PROGRO_yoy'] <= 200) {
                            $PROGRO_yoy_s = 92;
                        } elseif ($fieldArr['PROGRO_yoy'] >= 200 && $fieldArr['PROGRO_yoy'] <= 500) {
                            $PROGRO_yoy_s = 94;
                        } else {
                            $PROGRO_yoy_s = 97;
                        }
                        if ($yearKey == 2017) $PROGRO_yoy += $PROGRO_yoy_s * 0.1;
                        if ($yearKey == 2018) $PROGRO_yoy += $PROGRO_yoy_s * 0.3;
                        if ($yearKey == 2019) $PROGRO_yoy += $PROGRO_yoy_s * 0.6;
                        //==========================企业资产规模状况==========================
                        if ($fieldArr['ASSGRO'] < 0) {
                            $ASSGRO_s = 9;
                        } elseif ($fieldArr['ASSGRO'] >= 0.01 && $fieldArr['ASSGRO'] <= 10) {
                            $ASSGRO_s = 10;
                        } elseif ($fieldArr['ASSGRO'] >= 10.1 && $fieldArr['ASSGRO'] <= 50) {
                            $ASSGRO_s = 14;
                        } elseif ($fieldArr['ASSGRO'] >= 50.1 && $fieldArr['ASSGRO'] <= 300) {
                            $ASSGRO_s = 19;
                        } elseif ($fieldArr['ASSGRO'] >= 300.1 && $fieldArr['ASSGRO'] <= 500) {
                            $ASSGRO_s = 23.5;
                        } elseif ($fieldArr['ASSGRO'] >= 500.1 && $fieldArr['ASSGRO'] <= 1000) {
                            $ASSGRO_s = 31.5;
                        } elseif ($fieldArr['ASSGRO'] >= 1000.1 && $fieldArr['ASSGRO'] <= 5000) {
                            $ASSGRO_s = 42.5;
                        } elseif ($fieldArr['ASSGRO'] >= 5000.1 && $fieldArr['ASSGRO'] <= 10000) {
                            $ASSGRO_s = 51;
                        } elseif ($fieldArr['ASSGRO'] >= 10000.1 && $fieldArr['ASSGRO'] <= 30000) {
                            $ASSGRO_s = 62;
                        } elseif ($fieldArr['ASSGRO'] >= 30000.1 && $fieldArr['ASSGRO'] <= 50000) {
                            $ASSGRO_s = 71;
                        } elseif ($fieldArr['ASSGRO'] >= 50000.1 && $fieldArr['ASSGRO'] <= 100000) {
                            $ASSGRO_s = 76;
                        } elseif ($fieldArr['ASSGRO'] >= 100000.1 && $fieldArr['ASSGRO'] <= 500000) {
                            $ASSGRO_s = 82;
                        } elseif ($fieldArr['ASSGRO'] >= 500000.1 && $fieldArr['ASSGRO'] <= 1000000) {
                            $ASSGRO_s = 85;
                        } elseif ($fieldArr['ASSGRO'] >= 1000000.1 && $fieldArr['ASSGRO'] <= 5000000) {
                            $ASSGRO_s = 93;
                        } elseif ($fieldArr['ASSGRO'] >= 5000000.1 && $fieldArr['ASSGRO'] <= 10000000) {
                            $ASSGRO_s = 95;
                        } elseif ($fieldArr['ASSGRO'] >= 10000000.1 && $fieldArr['ASSGRO'] <= 50000000) {
                            $ASSGRO_s = 97;
                        } else {
                            $ASSGRO_s = 98;
                        }
                        if ($yearKey == 2017) $ASSGRO += $ASSGRO_s * 0.1;
                        if ($yearKey == 2018) $ASSGRO += $ASSGRO_s * 0.3;
                        if ($yearKey == 2019) $ASSGRO += $ASSGRO_s * 0.6;
                        //==========================企业资产增长状况==========================
                        if ($fieldArr['ASSGRO_yoy'] <= -50) {
                            $ASSGRO_yoy_s = 4;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= -50 && $fieldArr['ASSGRO_yoy'] <= -21) {
                            $ASSGRO_yoy_s = 8;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= -20 && $fieldArr['ASSGRO_yoy'] <= -11) {
                            $ASSGRO_yoy_s = 11;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= -10 && $fieldArr['ASSGRO_yoy'] <= -6) {
                            $ASSGRO_yoy_s = 16;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= -5 && $fieldArr['ASSGRO_yoy'] <= 0) {
                            $ASSGRO_yoy_s = 21;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 0 && $fieldArr['ASSGRO_yoy'] <= 5) {
                            $ASSGRO_yoy_s = 26;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 6 && $fieldArr['ASSGRO_yoy'] <= 10) {
                            $ASSGRO_yoy_s = 31;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 11 && $fieldArr['ASSGRO_yoy'] <= 25) {
                            $ASSGRO_yoy_s = 35;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 26 && $fieldArr['ASSGRO_yoy'] <= 30) {
                            $ASSGRO_yoy_s = 42;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 31 && $fieldArr['ASSGRO_yoy'] <= 50) {
                            $ASSGRO_yoy_s = 56;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 51 && $fieldArr['ASSGRO_yoy'] <= 70) {
                            $ASSGRO_yoy_s = 72;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 71 && $fieldArr['ASSGRO_yoy'] <= 100) {
                            $ASSGRO_yoy_s = 85;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 101 && $fieldArr['ASSGRO_yoy'] <= 200) {
                            $ASSGRO_yoy_s = 92;
                        } elseif ($fieldArr['ASSGRO_yoy'] >= 200 && $fieldArr['ASSGRO_yoy'] <= 500) {
                            $ASSGRO_yoy_s = 94;
                        } else {
                            $ASSGRO_yoy_s = 98;
                        }
                        if ($yearKey == 2017) $ASSGRO_yoy += $ASSGRO_yoy_s * 0.1;
                        if ($yearKey == 2018) $ASSGRO_yoy += $ASSGRO_yoy_s * 0.3;
                        if ($yearKey == 2019) $ASSGRO_yoy += $ASSGRO_yoy_s * 0.6;
                    }

                    $ext[$entNameKey] = [
                        'VENDINC' => round($VENDINC),
                        'VENDINC_yoy' => round($VENDINC_yoy),
                        'NETINCMAIBUSINC' => round($NETINCMAIBUSINC),
                        'PROGRO_yoy' => round($PROGRO_yoy),
                        'ASSGRO' => round($ASSGRO),
                        'ASSGRO_yoy' => round($ASSGRO_yoy),
                    ];

                    $temp['VENDINC'] = [];
                    $temp['PROGRO'] = [];
                    $temp['ASSGRO'] = [];

                    foreach ($ext as $myKey => $myVal) {
                        $temp['VENDINC'][] = [
                            'entName' => $myKey,
                            'score' => round($myVal['VENDINC'] * 0.7 + $myVal['VENDINC_yoy'] * 0.3),
                            'detail' => [
                                [
                                    'score' => $myVal['VENDINC'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业规模越大,2.通过分析与企业营收能力有关行为后的评估结果。主要供判断企业的规模状况',
                                    'features' => '企业规模状况',
                                ],
                                [
                                    'score' => $myVal['VENDINC_yoy'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业发展与经营的增速越高,2.通过分析与企业营收增长能力有关行为后的评估结果。主要反映企业的成长速度，供判断企业的高成长性价值',
                                    'features' => '企业成长性状况',
                                ],
                            ]
                        ];

                        $temp['PROGRO'][] = [
                            'entName' => $myKey,
                            'score' => round($myVal['NETINCMAIBUSINC'] * 0.7 + $myVal['PROGRO_yoy'] * 0.3),
                            'detail' => [
                                [
                                    'score' => $myVal['NETINCMAIBUSINC'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业盈利实力越强,2.通过分析可为企业贡献利润有关行为后的评估结果。主要反映企业当前的盈利水平',
                                    'features' => '企业盈利能力',
                                ],
                                [
                                    'score' => $myVal['PROGRO_yoy'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业持续盈利能力越强,2.通过分析可为企业贡献净利润有关行为，以及对应行为同比增速后的评估结果。主要反映企业的盈利趋势，供判断企业今后一段时期的盈利能力',
                                    'features' => '企业盈利可持续能力',
                                ],
                            ]
                        ];

                        $temp['ASSGRO'][] = [
                            'entName' => $myKey,
                            'score' => round($myVal['ASSGRO'] * 0.7 + $myVal['ASSGRO_yoy'] * 0.3),
                            'detail' => [
                                [
                                    'score' => $myVal['ASSGRO'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业资产规模越大,2.通过分析与企业资产有关行为后的评估结果。主要供判断企业的资产规模状况',
                                    'features' => '企业资产规模状况',
                                ],
                                [
                                    'score' => $myVal['ASSGRO_yoy'],
                                    'desc' => '1.按0分到100分划分，评分越高，企业规模增长的能力越强,2.通过分析与企业资产维度有关行为后的评估结果。主要反映企业的资产变化情况，供判断企业的整体规模与合作能力',
                                    'features' => '企业资产增长状况',
                                ],
                            ]
                        ];
                    }

                    $temp['VENDINC'] = control::sortArrByKey($temp['VENDINC'], 'score', 'desc', true);
                    $temp['PROGRO'] = control::sortArrByKey($temp['PROGRO'], 'score', 'desc', true);
                    $temp['ASSGRO'] = control::sortArrByKey($temp['ASSGRO'], 'score', 'desc', true);

                    //添加index
                    foreach ($temp as $mymyKey => $mymyVal) {
                        $index = 1;
                        foreach ($mymyVal as $w => $h) {
                            $temp[$mymyKey][$w]['index'] = $index;
                            $index++;
                        }
                    }

                }
            }
        }

        return $this->checkResponse($ready, $temp);
        //return $this->writeJson(200, null, $ready, '成功', true, $temp);
    }

}