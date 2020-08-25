<?php

namespace App\HttpController\Business\Api\QianQi;

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
        $res['Paging']=null;
        $res['Result']=$res['data'];
        $res['Message']=$res['msg'];

        return $this->writeJson((int)$res['code'],$res['Paging'],$res['Result'],$res['Message']);
    }

    //近三年的财务数据，不需要授权
    function getThreeYearsData()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'entName'=>$entName
        ];

        $res=(new QianQiService())->getThreeYearsData($postData);

        //改成同比，不能返回原值
        $res['data']=(new QianQiService())->toPercent($res['data']);

        return $this->checkResponse($res);
    }

    //近三年的财务数据，需要授权
    function getThreeYearsDataNeedAuth()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'entName'=>$entName
        ];

        $res=(new QianQiService())->getThreeYearsData($postData);

        return $this->checkResponse($res);
    }













}