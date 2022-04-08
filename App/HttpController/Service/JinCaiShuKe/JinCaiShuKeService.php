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
        $res['code'] !== '0000' ?: $res['code'] = 200;
        $arr['content'] = jsonDecode(base64_decode($res['content']));
        $arr['uuid'] = $res['uuid'];
        $res['Result'] = $arr;
        return $this->createReturn($res['code'], $res['Paging'] ?? null, $res['Result'], $res['msg'] ?? null);
    }

    //
    private function signature(array $content, string $nsrsbh, string $serviceid, string $signType): string
    {
        $content = base64_encode(jsonEncode($content, false));

        $arr = [
            'appid' => $this->appKey,
            'content' => $content,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => $nsrsbh,
            'serviceid' => $serviceid,
        ];

        $str = '?';

        foreach ($arr as $key => $val) {
            $str .= "{$key}={$val}&";
        }

        $str = rtrim($str, '&');

        return $signType === '0' ?
            base64_encode(hash_hmac('sha256', $str, $this->appSecret, true)) :
            strtoupper(md5(
                $this->appKey .
                $this->appSecret .
                $content .
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
            'fplxs' => '01,08,03,04,10,11,14,15',//发票类型 01-增值税专用发票 08-增值税专用发票（电子）03-机动车销售统一发票 ...
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
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //发票提取
    function S000523(string $nsrsbh, string $rwh, $page, $pageSize): array
    {
        $content = [
            'mode' => '2',
            'rwh' => trim($rwh),
            'page' => trim($page),
            'pageSize' => $pageSize - 0 > 1000 ? '1000' : trim($pageSize),
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
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }
}


