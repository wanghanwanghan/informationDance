<?php

namespace App\HttpController\Service\DaXiang;

use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

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
        $this->appKeyTest = 'Mlfs7n9kofqPMaNVJSFoDcwS';
        $this->appSecret = CreateConf::getInstance()->getConf('guopiao.key');
        $this->appSecretTest = 'awSW7gts8AS4StGV84HCKVCf';
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

    function test()
    {
        return (new CoHttpClient())
            ->useCache(false)
            ->send($this->urlTest, [
                'appKey' => $this->appKeyTest,
                'appSecret' => $this->appSecretTest,
            ], [], [], 'postjson');
    }
}
