<?php

namespace App\HttpController\Business\Api\LongXin;

use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Pay\ChargeService;

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
    private function checkResponse($res)
    {
        $res['Paging'] = null;

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['Result'] = $res['data'];
        $res['Message'] = $res['msg'];

        $charge = ChargeService::getInstance()->LongXin($this->request(), 51);

        if ($charge['code'] != 200) {
            return $this->writeJson((int)$charge['code'], null, null, $charge['msg'], false);
        } else {
            return $this->writeJson((int)$res['code'], $res['Paging'], $res['Result'], $res['Message'], false);
        }
    }

    //近n年的财务数据，不需要授权
    function getFinanceNotAuth()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $postData = [
            'entName' => $entName,
            'code' => '',
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
                $tmp[$year]['ASSGRO_yoy'] = $val['ASSGRO_yoy'];
                $tmp[$year]['LIAGRO_yoy'] = $val['LIAGRO_yoy'];
                $tmp[$year]['VENDINC_yoy'] = $val['VENDINC_yoy'];
                $tmp[$year]['MAIBUSINC_yoy'] = $val['MAIBUSINC_yoy'];
                $tmp[$year]['PROGRO_yoy'] = $val['PROGRO_yoy'];
                $tmp[$year]['NETINC_yoy'] = $val['NETINC_yoy'];
                $tmp[$year]['RATGRO_yoy'] = $val['RATGRO_yoy'];
                $tmp[$year]['TOTEQU_yoy'] = $val['TOTEQU_yoy'];
            }
            $res['data'] = $tmp;
        }

        return $this->checkResponse($res);
    }

    //近n年的财务数据，需要授权
    function getFinanceNeedAuth()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $beginYear = $this->request()->getRequestParam('year') ?? '';
        $dataCount = $this->request()->getRequestParam('dataCount') ?? '';

        $postData = [
            'entName' => $entName,
            'code' => '',
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,//取最近几年的
        ];

        //这里验证授权书是否审核通过

        $res = (new LongXinService())->getFinanceData($postData);

        return $this->checkResponse($res);
    }


}