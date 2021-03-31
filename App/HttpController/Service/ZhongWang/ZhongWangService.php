<?php

namespace App\HttpController\Service\ZhongWang;

use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ZhongWangService extends ServiceBase
{
    public $taxNo;
    public $key;
    public $keyTest;
    public $url;
    public $urlTest;

    function __construct()
    {
        parent::__construct();

        $this->taxNo = '91110108MA01KPGK0L';
        $this->key = CreateConf::getInstance()->getConf('zhongwang.key');
        $this->keyTest = CreateConf::getInstance()->getConf('zhongwang.keyTest');
        $this->url = CreateConf::getInstance()->getConf('zhongwang.url');
        $this->urlTest = CreateConf::getInstance()->getConf('zhongwang.urlTest');

        return true;
    }

    private function encrypt($str, $isTest = false)
    {
        $isTest === true ? $key = $this->keyTest : $key = $this->key;
        return openssl_encrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }

    private function decrypt($str, $isTest = false)
    {
        $isTest === true ? $key = $this->keyTest : $key = $this->key;
        return openssl_decrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }

    private function checkResp($res, $type)
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

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

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
            case 'getInvoiceMain':
            case 'getInvoiceGoods':
            case 'getEssential':
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

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], isset($res['msg']) ? $res['msg'] : null);
    }

    //进项销项发票详情 客户端（税盘）专用
    function getInOrOutDetailByClient($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['currentPage'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/getClientInvoices';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进项销项发票详情 证书专用
    function getInOrOutDetailByCert($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['invoiceType'] = '';//查询全部种类
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/invoiceCollection';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //实时ocr
    function getInvoiceOcr($image)
    {
        //图片steam的base64编码
        $body = $param = [];
        $param['content'] = $image;
        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/realTimeRecognize';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业授权认证
    function getAuthentication($entName, $callBackUrl, $orderNo)
    {
        $data = [
            'taxNo' => $this->taxNo,
            'companyName' => $entName,
            'callBackUrl' => $callBackUrl,
            'orderNo' => $orderNo,
        ];

        CommonService::getInstance()->log4PHP($data);

        $api_path = 'http://api.zoomwant.com:50001/data/information/getAuthentication';

        $res = $this->readyToSend($api_path, $data, false, false);

        CommonService::getInstance()->log4PHP($res);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票统计查询 目前不能用
    function getTaxInvoice($code, $start, $end)
    {
        $param['taxNumber'] = $code;
        $param['startDate'] = $start;
        $param['endDate'] = $end;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        CommonService::getInstance()->log4PHP($res);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项月度发票统计查询
    function getTaxInvoiceUpgrade($code, $start, $end)
    {
        $param['taxNumber'] = $code;
        $param['startDate'] = $start;
        $param['endDate'] = $end;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票信息
    function getInvoiceMain($code, $dataType, $startDate, $endDate, $page)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType - 0;
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page - 0;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票商品明细
    function getInvoiceGoods($code, $dataType, $startDate, $endDate, $page)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType - 0;
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page - 0;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业税务基本信息查询
    function getEssential($code)
    {
        $param['taxNumber'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业所得税-月（季）度申报表查询
    function getIncometaxMonthlyDeclaration($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业所得税-年报查询
    function getIncometaxAnnualReport($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //利润表 --年报查询
    function getFinanceIncomeStatementAnnualReport($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //利润表查询
    function getFinanceIncomeStatement($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //资产负债表-年度查询
    function getFinanceBalanceSheetAnnual($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //资产负债表查询
    function getFinanceBalanceSheet($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //增值税申报表查询
    function getVatReturn($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }


    //深度报告临时用的
    function getReceiptDataTest($code, $type)
    {
        if ($type === 'in') {
            $in = InvoiceIn::create()->where('purchaserTaxNo', $code)->all();
            return obj2Arr($in);
        } elseif ($type === 'out') {
            $out = InvoiceOut::create()->where('salesTaxNo', $code)->all();
            return obj2Arr($out);
        } elseif ($type === 'getCode') {
            return InvoiceIn::create()->where('purchaserName', $code)->get()->purchaserTaxNo;
        } else {
            return [];
        }
    }

    //统一发送
    private function readyToSend($api_path, $body, $isTest = false, $encryption = true)
    {
        if (preg_match('/^http/', $api_path)) {
            $url = $api_path;
        } elseif ($isTest) {
            $url = $this->urlTest . $api_path;
        } else {
            $url = $this->url . $api_path;
        }

        if ($encryption) {
            $param = $body['param'];
            $json_param = jsonEncode($param);
            $encryptedData = $this->encrypt($json_param, $isTest);
            $base64_str = base64_encode($encryptedData);
            $body['param'] = $base64_str;
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
            $res = base64_decode($res);
            $res = $this->decrypt($res, $isTest);
            return jsonDecode($res);
        } else {
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
            return $res;
        }
    }
}
