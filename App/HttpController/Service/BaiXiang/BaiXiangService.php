<?php

namespace App\HttpController\Service\BaiXiang;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class BaiXiangService extends ServiceBase
{
    private $test_url;

    function __construct()
    {
        parent::__construct();

        $this->test_url = 'https://testapi.bxtech.com/platform/v3';
        $this->test_app_id = 'GSUUR6O0';
        $this->test_secret = '9uhPsrkp0ySPAUkm33lMNu4qY69I6esnIVu2wtEAhKTDPiXkGg9HmjSa7cK7jLwM';
    }

    private function checkResp($res): array
    {
        $code = $res['CODE'] - 0;

        $paging = null;

        $result = empty($res['DATA']) ? null : $res['DATA'];

        $msg = $res['MSG'];

        return $this->createReturn($code, $paging, $result, $msg);
    }

    private function createHeader(string $uid, string $secret): array
    {
        $timestamp = time() . mt_rand(100, 999);
        $nonce = mt_rand(1, 9999999999) . '';
        $sign = sha1("{$nonce};{$secret};{$timestamp};{$uid};");

        return [
            'Content-Type' => 'application/json;charset=utf-8',
            'X-Uid' => $uid,
            'X-Timestamp' => $timestamp,
            'X-Nonce' => $nonce,
            'X-Signature' => $sign,
        ];
    }

    //药典通 企业详情
    function getDptEnterpriseMedicineDetailList($entname = '', $creditcode = '', $licreccode = '')
    {
        $post_data = [
            'entname' => trim($entname),
            'creditcode' => trim($creditcode),
            'licreccode' => trim($licreccode),
        ];

        $post_data = array_filter($post_data);

        $url = $this->test_url . '/dpt/enterprise/medicineDetailList';

        $header = $this->createHeader($this->test_app_id, $this->test_secret);

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $post_data, $header, [], 'get');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //药典通 药品详情
    function getDptDrugDetail($drugcode = '')
    {
        $post_data = [
            'drugcode' => trim($drugcode),
        ];

        $post_data = array_filter($post_data);

        $url = $this->test_url . '/dpt/drug/detail';

        $header = $this->createHeader($this->test_app_id, $this->test_secret);

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $post_data, $header, [], 'get');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }
}
