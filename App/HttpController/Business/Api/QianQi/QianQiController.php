<?php

namespace App\HttpController\Business\Api\QianQi;

use App\HttpController\Service\Pay\ChargeService;
use App\HttpController\Service\QianQi\QianQiService;

class QianQiController extends QianQiBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验乾启返回值，并给客户计费
    private function checkResponse($res)
    {
        $res['Paging'] = null;

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        $res['Result'] = $res['data'];
        $res['Message'] = $res['msg'];

        $charge = ChargeService::getInstance()->QianQi($this->request(), 0);

        if ($charge['code'] != 200) {
            return $this->writeJson((int)$charge['code'], null, null, $charge['msg']);
        } else {
            return $this->writeJson((int)$res['code'], $res['Paging'], $res['Result'], $res['Message']);
        }
    }

    //近三年的财务数据，不需要授权
    function getThreeYearsData()
    {
        $entName = $this->request()->getRequestParam('entName');

        $postData = [
            'entName' => $entName
        ];

        $res = (new QianQiService())->getThreeYearsData($postData);

        //改成同比，不能返回原值
        $res['data'] = (new QianQiService())->toPercent($res['data']);

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

        $res = (new QianQiService())->getThreeYearsData($postData);

        return $this->checkResponse($res);
    }

    //天眼查取数据测试
    function getDataTest()
    {
        $res = (new QianQiService())->getDataTest();

        return $this->writeJson(200, null, $res);
    }


}