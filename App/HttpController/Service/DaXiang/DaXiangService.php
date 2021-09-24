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
    public $appKey;
    public $appSecret;

    function __construct()
    {
        parent::__construct();

        $this->taxNo = '91110108MA01KPGK0L';
        $this->appKey = 'JczSaWGP76LYdIOfHds52Thk';
        $this->appSecret = 'BszCebdj6nOglZLBrYYUspWl';

        return true;
    }

    //
    private function createToken(): string
    {
        $appKey = 'Mlfs7n9kofqPMaNVJSFoDcwS';
        $appSecret = 'awSW7gts8AS4StGV84HCKVCf';

        $token_info = (new CoHttpClient())
            ->useCache(false)
            ->send('https://openapi.ele-cloud.com/api/authen/token', [
                'appKey' => $this->appKey,
                'appSecret' => $this->appSecret,
            ], [], [], 'postjson');

        return $token_info['access_token'];
    }

    //
    function getInv($entCode, $page, $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ): array
    {
        $url = 'https://openapi.ele-cloud.com/api/business-credit/v3/queryEntInvoicePage';
        $token = $this->createToken();
        list($usec, $sec) = explode(' ', microtime());
        $cn_time = date('YmdHis', time()) . round($usec * 1000);
        $id = str_pad($cn_time, 17, '0', STR_PAD_RIGHT) . str_pad(mt_rand(1, 999999), 15, '0', STR_PAD_RIGHT);
        $arr = [
            'zipCode' => '0',
            'encryptCode' => '0',
            'dataExchangeId' => $id . '',
            'entCode' => $entCode,// || $this->taxNo,
            'content' => base64_encode(jsonEncode([
                'page' => $page . '',
                'NSRSBH' => $NSRSBH,
                'KM' => $KM . '',//1进项 2销项发票
                'FPLXDM' => $FPLXDM . '',//发票类型
                'KPKSRQ' => $KPKSRQ,
                'KPJSRQ' => $KPJSRQ,
            ]))
        ];

        //01增值税专用发票
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票
        //10增值税普通发票电子
        //11增值税普通发票卷式
        //14通行费电子票
        //15二手车销售统一发票

        return (new CoHttpClient())
            ->useCache(false)
            ->send($url . "?access_token={$token}", $arr, [], [], 'postjson');
    }
}


