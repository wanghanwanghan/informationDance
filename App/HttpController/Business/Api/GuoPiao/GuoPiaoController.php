<?php

namespace App\HttpController\Business\Api\GuoPiao;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\Pay\ChargeService;
use wanghanwanghan\someUtils\control;

class GuoPiaoController extends GuoPiaoBase
{
    public $moduleNum;//扣费的id
    public $entName;//扣费用的entName

    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //检验法研院返回值，并给客户计费
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

        $res['code'] - 0 === 0 ? $res['code'] = 200 : $res['code'] = 600;

        //拿结果
        switch ($type) {
            case 'getReceiptDetailByClient':
            case 'getReceiptDetailByCert':
                $res['Result'] = $res['data']['invoices'];
                break;
            case 'getInvoiceOcr':
            case 'sendCertificateAccess':
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

        if ($this->moduleNum === 54) {
            $charge = ChargeService::getInstance()->TwoTable($this->request(), $this->moduleNum, $this->entName);
            if ($charge['code'] !== 200) {
                return $this->writeJson($charge['code'], null, null, $charge['msg'], false);
            }
        }

        return $writeJson !== true ? [
            'code' => $res['code'],
            'paging' => $res['Paging'],
            'result' => $res['Result'],
            'msg' => $res['msg'] ?? null,
        ] : $this->writeJson($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    //发票详情（进项销项）3.4
    function getReceiptDetailByClient()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';
        $endDate = $this->request()->getRequestParam('endDate') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = (new GuoPiaoService())->getInOrOutDetailByClient($code, $type, $startDate, $endDate, $page, $pageSize);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //发票详情（进项销项）3.4
    function getReceiptDetailByCert()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $type = $this->request()->getRequestParam('type') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';
        $endDate = $this->request()->getRequestParam('endDate') ?? '';
        $page = $this->request()->getRequestParam('page') ?? 1;
        $pageSize = $this->request()->getRequestParam('pageSize') ?? 10;

        $res = (new GuoPiaoService())->getInOrOutDetailByCert($code, $type, $startDate, $endDate, $page, $pageSize);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //上传授权书 3.4
    function sendCertificateAccess()
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $delegateCert = $this->request()->getRequestParam('delegateCert') ?? '';//目前传授权书url
        $fileType = 1;//1:pdf 2:zip
        $taxNature = $this->request()->getRequestParam('taxNature') ?? 0;//0:小规模纳税人,1:一般纳税人,2:转登记
        $taxAuthorityCode = '1';//主管税务机关代码
        $taxAuthorityName = '1';//主管税务机关名称

        $taxNo = $this->request()->getRequestParam('taxNo') ?? '';//企业税号
        $taxName = $this->request()->getRequestParam('taxName') ?? '';//企业名称
        $areaName = $this->request()->getRequestParam('areaName') ?? '';//填税号所属省份（宁波，青岛，厦门除外）

        $certificate = [[
            'taxNo' => $taxNo,
            'taxName' => $taxName,
            'areaName' => $areaName,
        ]];

        $res = (new GuoPiaoService())->sendCertificateAccess(
            $delegateCert, $fileType, $taxNature, $taxAuthorityCode, $taxAuthorityName, $certificate
        );

        $callResult = empty($res['data']) ? null : $res['data']['callResult'];

        //授权书记录
        try {
            $authBookInfo = AuthBook::create()->where([
                'phone' => $phone,
                'entName' => $taxName,
                'code' => $taxNo,
                'name' => $delegateCert,
                'type' => 3,
            ])->get();
            if (!empty($authBookInfo)) {
                if ($authBookInfo->status === 2) {
                    $authBookInfo->update([
                        'name' => $delegateCert,
                        'status' => 1,
                        'remark' => $callResult,
                    ]);
                }
            } else {
                AuthBook::create()->data([
                    'phone' => $phone,
                    'entName' => $taxName,
                    'code' => $taxNo,
                    'name' => $delegateCert,
                    'status' => 1,
                    'type' => 3,
                    'remark' => $callResult,
                ])->save();
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        return $this->checkResponse($res, __FUNCTION__);
    }

    //发票实时ocr
    function getInvoiceOcr()
    {
        $image = $this->request()->getRequestParam('image') ?? '';

        $res = (new GuoPiaoService())->getInvoiceOcr($image);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //发票实时ocr api 实时识别图片接口
    function getInvoiceOcrV2()
    {
        $fileName = $this->request()->getRequestParam('fileName') ?? '';
        $base64Content = $this->request()->getRequestParam('base64Content') ?? '';
        $imageUrl = $this->request()->getRequestParam('imageUrl') ?? '';

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->realTimeRecognize($fileName,$base64Content,$imageUrl);

        return $this->writeJson($res['code'], null, $res['result'], $res['message']);
    }

    //发票查验接⼝(API)
    function checkInvoice()
    {
        $invoiceCode = $this->request()->getRequestParam('invoiceCode') ?? '';
        $base64Content = $this->request()->getRequestParam('base64Content') ?? '';
        $billingDate = $this->request()->getRequestParam('billingDate') ?? '';
        $totalAmount = $this->request()->getRequestParam('totalAmount') ?? '';
        $checkCode = $this->request()->getRequestParam('checkCode') ?? '';

        $res = (new GuoPiaoService())->setCheckRespFlag(true)->checkInvoice($invoiceCode, $base64Content, $billingDate, $totalAmount, $checkCode);

        return $this->writeJson($res['code'], null, $res['result'], $res['message']);
    }


    //企业授权认证
    function getAuthentication(): bool
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $entName = $this->request()->getRequestParam('entName') ?? '';
        $code = $this->request()->getRequestParam('code') ?? '';
        $callback = $this->request()->getRequestParam('callback') ?? '';

        if (empty($callback)) {
            $callback = "https://api.meirixindong.com/api/v1/user/addAuthEntName?entName={$entName}&phone={$phone}";
        }

        $orderNo = $phone . time();

        $res = (new GuoPiaoService())->getAuthentication($entName, $callback, $orderNo);

        $res = jsonDecode($res);

        CommonService::getInstance()->log4PHP($res);

        !(isset($res['code']) && $res['code'] == 0) ?: $res['code'] = 200;

        //添加授权信息
        try {
            $check = AuthBook::create()->where([
                'phone' => $phone, 'entName' => $entName, 'code' => $code, 'type' => 2
            ])->get();
            if (empty($check)) {
                AuthBook::create()->data([
                    'phone' => $phone,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,//深度报告，发票数据
                    'remark' => $orderNo
                ])->save();
            } else {
                $check->update([
                    'phone' => $phone,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,
                    'remark' => $orderNo
                ]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (strpos($res['data'], '?url=')) {
            $arr = explode('?url=', $res['data']);
            $res['data'] = 'https://api.meirixindong.com/Static/vertify.html?url=' . $arr[1];
        }

        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }

    //进销项发票统计查询 目前不能用
    function getTaxInvoice()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')

        $res = (new GuoPiaoService())->getTaxInvoice($code, $startDate, $endDate);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项月度发票统计查询
    function getTaxInvoiceUpgrade()
    {
        $code = $this->request()->getRequestParam('code') ?? '';
        $startDate = $this->request()->getRequestParam('startDate') ?? '';//date('Y-m-d')
        $endDate = $this->request()->getRequestParam('endDate') ?? '';//date('Y-m-d')

        $res = (new GuoPiaoService())->getTaxInvoiceUpgrade($code, $startDate, $endDate);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项发票信息 信动专用
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

        $res = (new GuoPiaoService())->getInvoiceMain($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //进销项发票商品明细 信动专用
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

        $res = (new GuoPiaoService())->getInvoiceGoods($code, $dataType, $startDate, $endDate, $page);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业税务基本信息查询
    function getEssential()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getEssential($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业所得税-月（季）度申报表查询
    function getIncometaxMonthlyDeclaration()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getIncometaxMonthlyDeclaration($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year_month = substr(str_replace(['-'], '', $row['beginDate']), 0, 6) . '';
                if (!isset($model[$year_month])) {
                    $model[$year_month] = [];
                }
                $row['sequence'] = $row['sequence'] - 0;
                $model[$year_month][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'sequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        return $this->checkResponse($res, __FUNCTION__);
    }

    //企业所得税-年报查询
    function getIncometaxAnnualReport()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getIncometaxAnnualReport($code);

        return $this->checkResponse($res, __FUNCTION__);
    }

    //利润表 -- 年报查询
    function getFinanceIncomeStatementAnnualReport()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getFinanceIncomeStatementAnnualReport($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year = substr($row['beginDate'], 0, 4) . '';
                if (!isset($model[$year])) {
                    $model[$year] = [];
                }
                $row['sequence'] = $row['sequence'] - 0;
                $model[$year][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'sequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        $this->moduleNum = 54;
        $this->entName = $code;

        return $this->checkResponse($res, __FUNCTION__);
    }

    //利润表查询
    function getFinanceIncomeStatement()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getFinanceIncomeStatement($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year_month = substr(str_replace(['-'], '', $row['beginDate']), 0, 6) . '';
                if (!isset($model[$year_month])) {
                    $model[$year_month] = [];
                }
                $row['sequence'] = $row['sequence'] - 0;
                $model[$year_month][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'sequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        $this->moduleNum = 54;
        $this->entName = $code;

        return $this->checkResponse($res, __FUNCTION__);
    }

    //资产负债表 -- 年度查询
    function getFinanceBalanceSheetAnnual()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getFinanceBalanceSheetAnnual($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year = substr($row['beginDate'], 0, 4) . '';
                if (!isset($model[$year])) {
                    $model[$year] = [];
                }
                $row['columnSequence'] = $row['columnSequence'] - 0;
                $model[$year][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'columnSequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        $this->moduleNum = 54;
        $this->entName = $code;

        return $this->checkResponse($res, __FUNCTION__);
    }

    //资产负债表查询
    function getFinanceBalanceSheet()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getFinanceBalanceSheet($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year_month = substr(str_replace(['-'], '', $row['beginDate']), 0, 6) . '';
                if (!isset($model[$year_month])) {
                    $model[$year_month] = [];
                }
                $row['columnSequence'] = $row['columnSequence'] - 0;
                $model[$year_month][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'columnSequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        $this->moduleNum = 54;
        $this->entName = $code;

        return $this->checkResponse($res, __FUNCTION__);
    }

    //增值税申报表查询
    function getVatReturn()
    {
        $code = $this->request()->getRequestParam('code') ?? '';

        $res = (new GuoPiaoService())->getVatReturn($code);

        //正常
        if ($res['code'] - 0 === 0 && !empty($res['data'])) {
            $data = jsonDecode($res['data']);
            $model = [];
            foreach ($data as $row) {
                $year_month = substr(str_replace(['-'], '', $row['beginDate']), 0, 6) . '';
                if (!isset($model[$year_month])) {
                    $model[$year_month] = [];
                }
                $row['sequence'] = $row['sequence'] - 0;
                $model[$year_month][] = $row;
            }
            //排序
            foreach ($model as $year => $val) {
                $model[$year] = control::sortArrByKey($val, 'sequence', 'asc', true);
            }
            $res['data'] = jsonEncode($model);
        }

        return $this->checkResponse($res, __FUNCTION__);
    }


}