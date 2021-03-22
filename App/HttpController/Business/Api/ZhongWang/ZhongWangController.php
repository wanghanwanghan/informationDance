<?php

namespace App\HttpController\Business\Api\ZhongWang;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\Score\xds;
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
            case 'getReceiptDetailByCert':
                $res['Result'] = $res['data']['invoices'];
                break;
            case 'getInvoiceOcr':
                $res['Result'] = empty($res['data']) ? null : current($res['data']);
                break;
            case 'getTaxInvoiceUpgrade':
            case 'getEssential':
            case 'getInvoiceMain':
            case 'getInvoiceGoods':
                $res['Result'] = empty($res['data']) ? null : $res['data'];
                break;
            case 'getIncometaxMonthlyDeclaration':
            case 'getIncometaxAnnualReport':
            case 'getFinanceIncomeStatementAnnualReport':
            case 'getFinanceIncomeStatement':
            case 'getFinanceBalanceSheetAnnual':
            case 'getFinanceBalanceSheet':
            case 'getVatReturn':
                $res['Result'] = is_string($res['data']) ? jsonDecode($res['data']) : $res['data'];
                break;
            default:
                $res['Result'] = null;
        }

        return $writeJson !== true ? [
            'code' => $res['code'],
            'paging' => $res['Paging'],
            'result' => $res['Result'],
            'msg' => isset($res['msg']) ? $res['msg'] : null,
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
        $code = $this->request()->getRequestParam('code') ?? '';
        $callback = $this->request()->getRequestParam('callback') ?? 'https://www.baidu.com/';
        $orderId = $this->request()->getRequestParam('orderId') ?? '';

        $res = (new ZhongWangService())->getAuthentication($entName, $callback);

        $res = jsonDecode($res);

        !(isset($res['code']) && $res['code'] == 0) ?: $res['code'] = 200;

        $res['message'] = xds::getInstance()->get();

        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }

    //进销项发票统计查询 目前不能用
    function getTaxInvoice()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')

        $res = (new ZhongWangService())->getTaxInvoice($code, $startDate, $endDate);

        CommonService::getInstance()->log4PHP($res);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项月度发票统计查询
    function getTaxInvoiceUpgrade()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')

        $res = (new ZhongWangService())->getTaxInvoiceUpgrade($code, $startDate, $endDate);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项发票信息
    function getInvoiceMain()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $dataType = $this->request()->getRequestParam('dataType') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')
        $page = $this->request()->getRequestParam('page') ?? '';

        if (empty($code) || empty($startDate) || empty($endDate))
            return $this->writeJson(201, null, null, '参数不能是空');

        if (!is_numeric($dataType) || !is_numeric($page))
            return $this->writeJson(201, null, null, '参数必须是数字');

        $res = (new ZhongWangService())->getInvoiceMain($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项发票商品明细
    function getInvoiceGoods()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $dataType = $this->request()->getRequestParam('dataType') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')
        $page = $this->request()->getRequestParam('page') ?? '';

        if (empty($code) || empty($startDate) || empty($endDate))
            return $this->writeJson(201, null, null, '参数不能是空');

        if (!is_numeric($dataType) || !is_numeric($page))
            return $this->writeJson(201, null, null, '参数必须是数字');

        $res = (new ZhongWangService())->getInvoiceGoods($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业税务基本信息查询
    function getEssential()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getEssential($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业所得税-月（季）度申报表查询
    function getIncometaxMonthlyDeclaration()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getIncometaxMonthlyDeclaration($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业所得税-年报查询
    function getIncometaxAnnualReport()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getIncometaxAnnualReport($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //利润表 --年报查询
    function getFinanceIncomeStatementAnnualReport()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getFinanceIncomeStatementAnnualReport($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //利润表查询
    function getFinanceIncomeStatement()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getFinanceIncomeStatement($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //资产负债表-年度查询
    function getFinanceBalanceSheetAnnual()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getFinanceBalanceSheetAnnual($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //资产负债表查询
    function getFinanceBalanceSheet()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getFinanceBalanceSheetAnnual($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //增值税申报表查询
    function getVatReturn()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new ZhongWangService())->getVatReturn($code);

        return $this->checkResponse($res, __FUNCTION__);
    }


}