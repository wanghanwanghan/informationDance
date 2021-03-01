<?php

namespace App\HttpController\Business\Api\LongXin;

use App\HttpController\Models\EntDb\EntDbFinance;
use App\HttpController\Service\CreateTable\CreateTableService;
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
    function getThreeYearsData()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'entName' => $entName,
            'beginYear' => date('Y'),
            'dataCount' => 2,//取最近几年的
        ];

        $res = (new LongXinService())->getThreeYearsData($postData);

        return $this->checkResponse($res);
    }

    //近三年的财务数据，需要授权
    function getThreeYearsDataNeedAuth()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'entName' => $entName
        ];

        //这里验证授权书是否审核通过

        $res = (new LongXinService())->getThreeYearsData($postData);

        return $this->checkResponse($res);
    }


}