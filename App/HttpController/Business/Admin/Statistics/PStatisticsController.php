<?php

namespace App\HttpController\Business\Admin\Statistics;

use App\HttpController\Models\Provide\RequestRecode;

class PStatisticsController extends StatisticsBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getStatisticsList()
    {
        $uid = $this->getRequestData('uid');
        $aid = $this->getRequestData('aid');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 15);

        $data = RequestRecode::create()->addSuffix(2020)->alias('t1')
            ->join('information_dance_request_user_info as t2', 't1.userId = t2.id', 'left')
            ->join('information_dance_request_api_info as t3', 't1.provideApiId = t3.id', 'left')
            ->field([
                't1.id',
                't1.requestId',
                't1.requestIp',
                't1.requestData',
                't1.responseCode',
                't1.responseData',
                't1.spendTime',
                't1.spendMoney',
                't1.created_at',
                't2.username',
                't3.path',
                't3.name',
                't3.desc',
                't3.source',
                't3.price',
            ])->limit($this->exprOffset($page, $pageSize), $pageSize)->all();





        return $this->writeJson(200, null, $data);
    }


}