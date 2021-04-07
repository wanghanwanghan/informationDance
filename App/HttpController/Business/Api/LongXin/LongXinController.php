<?php

namespace App\HttpController\Business\Api\LongXin;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;
use Carbon\Carbon;

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


}