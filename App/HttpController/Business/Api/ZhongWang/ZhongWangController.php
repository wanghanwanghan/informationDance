<?php

namespace App\HttpController\Business\Api\ZhongWang;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ZhongWang\ZhongWangService;
use wanghanwanghan\someUtils\moudles\ioc\ioc;

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

    //检验法海返回值，并给客户计费
    private function checkResponse($res, $type, $writeJson = true)
    {
        if (isset($res['data']['total']) &&
            isset($res['data']['pageSize']) &&
            isset($res['data']['currentPage'])) {
            $res['Paging'] = [
                'page' => $res['data']['currentPage'],
                'pageSize' => $res['data']['pageSize'],
                'total' => $res['data']['total'],
            ];
        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500, $res['Paging'], [], 'co请求错误');

        (int)$res['code'] === 0 ? $res['code'] = 200 : $res['code'] = 600;

        //拿结果
        switch ($type) {
            case 'getReceiptDetailByClient':
                $step = 1;
                $res['Result'] = $res['data']['invoices'];
                break;
            case 'getReceiptDetailByCert':
                $step = 2;
                $res['Result'] = $res['data']['invoices'];
                break;
            default:
                $res['Result'] = null;
        }

        return $writeJson !== true ? [
            'code' => $res['code'],
            'paging' => $res['Paging'],
            'result' => $res['Result'],
            'msg' => $res['msg']
        ] : $this->writeJson($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    //发票详情（进项销项）
    function getReceiptDetailByClient()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';
        $endDate = $this->request()->getRequestParam('endDate') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = (new ZhongWangService())->getInOrOutDetailByClient($code, $type, $startDate, $endDate, $page, $pageSize);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //发票详情（进项销项）
    function getReceiptDetailByCert()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';
        $endDate = $this->request()->getRequestParam('endDate') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = (new ZhongWangService())->getInOrOutDetailByCert($code, $type, $startDate, $endDate, $page, $pageSize);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //发票实时ocr
    function getInvoiceOcr()
    {
        $image = $this->request()->getRequestParam('image') ?? '';

        return $this->writeJson(200, null, $image);
    }

    //企业授权认证
    function getAuthentication()
    {
        $entName = $this->request()->getRequestParam('entName') ?? '';

        $res = (new ZhongWangService())->getAuthentication($entName);

        return $this->checkResponse($res, __FUNCTION__);
    }


    //进销项发票统计查询
    function getTaxInvoice()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')

        $res = (new ZhongWangService())->getTaxInvoice($code, $startDate, $endDate);

        return $this->checkResponse($res, __FUNCTION__);
    }


}