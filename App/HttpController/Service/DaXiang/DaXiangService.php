<?php

namespace App\HttpController\Service\DaXiang;

use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class DaXiangService extends ServiceBase
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
        $this->appKey = CreateConf::getInstance()->getConf('guopiao.key');
        $this->appKeyTest = 'FqLHA2j4XL52x5yPOk5nPki6';
        $this->appSecret = CreateConf::getInstance()->getConf('guopiao.key');
        $this->appSecretTest = 'pw6X9obZGMPVsxQ5TBP76qRW';
        $this->url = CreateConf::getInstance()->getConf('guopiao.url');
        $this->urlTest = 'https://sandbox.ele-cloud.com/api/authen/token';

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

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg'] ?? null);
    }

    private function createToken(): string
    {
        $token_info = (new CoHttpClient())
            ->useCache(false)
            ->send('https://sandbox.ele-cloud.com/api/authen/token', [
                'appKey' => 'JczSaWGP76LYdIOfHds52Thk',
                'appSecret' => 'BszCebdj6nOglZLBrYYUspWl',
            ], [], [], 'postjson');
        return $token_info['access_token'];
    }

    function getInv()
    {
        $url = 'https://sandbox.ele-cloud.com/api/business-credit/v3/queryEntInvoicePage';
        $token = $this->createToken();
        list($usec, $sec) = explode(' ', microtime());
        $cn_time = date('YmdHis', time()) . round($usec * 1000);
        $id = str_pad($cn_time, 17, '0', STR_PAD_RIGHT) . str_pad(mt_rand(1, 999999), 15, '0', STR_PAD_RIGHT);
        $arr = [
            'zipCode' => '0',
            'encryptCode' => '0',
            'dataExchangeId' => $id . '',
            'entCode' => '91110108MA01KPGK0L',
            'content' => base64_encode(jsonEncode([
                'page' => '1',
                'NSRSBH' => $this->taxNo,
                'KM' => '1',//1进项 2销项发票
                'FPLXDM' => '04',//发票类型
                'KPKSRQ' => '1970-01-01',
                'KPJSRQ' => '2021-08-01',
            ]))
        ];
        $info = (new CoHttpClient())->useCache(false)
            ->send($url . "?access_token={$token}", $arr, [], [], 'postjson');
        CommonService::getInstance()->log4PHP($arr);
        CommonService::getInstance()->log4PHP($info);
    }
}


