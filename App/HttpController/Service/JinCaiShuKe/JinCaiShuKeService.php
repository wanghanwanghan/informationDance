<?php

namespace App\HttpController\Service\JinCaiShuKe;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class JinCaiShuKeService extends ServiceBase
{
    public $url;
    public $jtnsrsbh;
    public $appKey;
    public $appSecret;

    function __construct()
    {
        parent::__construct();

        $this->url = 'https://pubapi.jcsk100.com/pre/api/';
        $this->jtnsrsbh = '91110108MA01KPGK0L';
        $this->appKey = '1f58a6db7805';
        $this->appSecret = '3ab58912f92493131aa2';

        return true;
    }

    //
    private function checkResp($res): array
    {
        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg'] ?? null);
    }

    //
    private function signature(array $content, string $nsrsbh, string $serviceid, string $signType): string
    {
        $arr = [
            'appid' => $this->appKey,
            'content' => base64_encode(jsonEncode($content, false)),
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => $nsrsbh,
            'serviceid' => $serviceid,
        ];

        $str = '?' . http_build_query($arr);

        return $signType === '0' ?
            hash_hmac('sha256', $str, $this->appSecret) :
            strtoupper(md5(
                $this->appKey .
                $this->appSecret .
                base64_encode(jsonEncode($content, false)) .
                $this->jtnsrsbh .
                $nsrsbh .
                $serviceid
            ));
    }

    //发票归集
    function S000519(string $nsrsbh, string $start, string $stop): array
    {
        $content = [
            'sjlxs' => '1,2',//数据类型 1:进项票 2:销项票
            'fplxs' => '01,08,03,04,10,11,14,15,17',//发票类型 01-增值税专用发票 08-增值税专用发票（电子）03-机动车销售统一发票 ...
            'kprqq' => trim($start),//开票(填发)日期起 YYYY-MM-DD
            'kprqz' => trim($stop),//开票(填发)日期止 日期起止范围必须在同一个月内
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->setCheckRespFlag(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        CommonService::getInstance()->log4PHP(['请求的' => $content]);
        CommonService::getInstance()->log4PHP(['请求体' => $post_data]);
        CommonService::getInstance()->log4PHP(['返回的' => $res]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }
}


