<?php

namespace App\HttpController\Service\ZhongWang;

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

    private function encrypt($str)
    {
        return openssl_encrypt($str, 'aes-128-ecb', $this->keyTest, OPENSSL_RAW_DATA);
    }

    private function decrypt($str)
    {
        return openssl_decrypt($str, 'aes-128-ecb', $this->keyTest, OPENSSL_RAW_DATA);
    }

    //进项销项发票详情
    public function getInOrOutDetail($code, $dataType, $startDate, $endDate, $page, $pageSize)
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

        $response_body = $this->readyToSend($api_path, $body);

        return $response_body;
    }

    private function readyToSend($api_path, $body)
    {
        $param = $body['param'];
        $json_param = jsonEncode($param);
        $encryptedData = $this->encrypt($json_param);
        $base64_str = base64_encode($encryptedData);
        $body['param'] = $base64_str;

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)
            ->send($this->urlTest . $api_path, http_build_query($body));

        CommonService::getInstance()->log4PHP($res);

        $res = base64_decode($res);

        CommonService::getInstance()->log4PHP($res);

        $res = $this->decrypt($res);

        CommonService::getInstance()->log4PHP($res);
        CommonService::getInstance()->log4PHP($this->keyTest);

        return $res;
    }
}
