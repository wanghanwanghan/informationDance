<?php

namespace App\HttpController\Business\Api\ZhongWang;

use App\HttpController\Service\ZhongWang\ZhongWangService;

class ZhongWangController extends ZhongWangBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //进项发票详情
    function getInReceiptDetail()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';
        $endDate = $this->request()->getRequestParam('endDate') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = (new ZhongWangService())->setCheckRespFlag(true)->getInOrOutDetail($code, 2, $startDate, $endDate, $page, $pageSize);

        return $this->writeJson(200,null,$res,'成功');
    }

}