<?php

namespace App\HttpController\Service\YiZhangTong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class YiZhangTongService extends ServiceBase
{
    private $test_url;
    private $test_app_id;
    private $test_channel;
    private $test_app_secret;
    private $test_rsa_pub;

    private $header = [
        'Content-Type' => 'application/json;charset=UTF-8',
    ];
    private $time;
    private $ak;
    private $send_ak;

    function __construct()
    {
        parent::__construct();
        $this->test_url = 'https://smelp-wg-web-stg1.ocft.com/smelp-wg/smelp_wg';
        $this->test_app_id = 'APP_1400';
        $this->test_channel = '1009';
        $this->test_app_secret = 'dHKq/fx1/7F9lUWkHhv1Dw==';
        $this->test_rsa_pub = <<<str
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCZKs+rCLRSPeS0ioV9lLDztKF3
8INbwuKt4U5YrbG0kg7O9KxyDaA2X2OtwO2YZDZ5S71/bgGyxaHbmdwzEuEJT0iy
7St8/U609nOQuRZsYtAsWfkCesjiXDJUk54ZhlZwo6NxeBRfhgJnwz/772DusCEu
tv6KR7pT0nAqXHIyXQIDAQAB
-----END PUBLIC KEY-----
str;
        $this->time = time() . mt_rand(100, 999);

        $this->ak = control::getUuid(16);
        $pkey = openssl_pkey_get_public($this->test_rsa_pub);
        openssl_public_encrypt($this->ak, $send_ak, $pkey);
        $this->send_ak = base64_encode($send_ak);
    }

    private function checkResp($res): array
    {
        $res['responseCode'] - 0 === 0 ? $code = 200 : $code = $res['responseCode'];

        if (isset($res['responseData']['result']['count'])) {
            $paging = [
                'total' => $res['responseData']['result']['count'],
            ];
        } else {
            $paging = null;
        }

        $result = $res['responseData'];

        $msg = $res['responseMessage'];

        return $this->createReturn($code, $paging, $result, $msg);
    }

    private function createMsg(array $data): string
    {
        $str = base64_encode(
            openssl_encrypt(
                jsonEncode($data, false), 'AES-128-CBC', $this->ak, OPENSSL_RAW_DATA, 'hanasian12345678'
            )
        );

        return str_replace(['\\r', '\\n'], '', $str);
    }

    private function createSign(string $msg, string $ser_id): string
    {
        return hash('sha256', $msg . $this->test_app_secret . $this->time . $ser_id . $this->test_channel);
    }

    //产品列表接口
    function getProductList()
    {
        $ser_id = '1001100057';

        $msg = $this->createMsg([
            'channelCode' => 'XWD',
        ]);

        $post_data = [
            'serviceId' => $ser_id,
            'appId' => $this->test_app_id,
            'requestId' => control::getUuid(),
            'timestamp' => $this->time,
            'channel' => $this->test_channel,
            'signture' => $this->createSign($msg, $ser_id),
            'ak' => $this->send_ak,
            'message' => urlencode($msg),
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->test_url, $post_data, $this->header, [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //静默注册/登陆功能接口
    function getLogin(array $msg)
    {
        $ser_id = '1001100058';
        $msg['channelCode'] = 'XWD';

        CommonService::getInstance()->log4PHP(array_filter($msg));

        $msg = $this->createMsg(array_filter($msg));

        $post_data = [
            'serviceId' => $ser_id,
            'appId' => $this->test_app_id,
            'requestId' => control::getUuid(),
            'timestamp' => $this->time,
            'channel' => $this->test_channel,
            'signture' => $this->createSign($msg, $ser_id),
            'ak' => $this->send_ak,
            'message' => urlencode($msg),
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->test_url, $post_data, $this->header, [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //订单列表查询
    function getOrderList(array $msg)
    {
        $ser_id = '1001100060';
        $msg['channelAgent'] = 'XWD';

        CommonService::getInstance()->log4PHP(array_filter($msg));

        $msg = $this->createMsg(array_filter($msg));

        $post_data = [
            'serviceId' => $ser_id,
            'appId' => $this->test_app_id,
            'requestId' => control::getUuid(),
            'timestamp' => $this->time,
            'channel' => $this->test_channel,
            'signture' => $this->createSign($msg, $ser_id),
            'ak' => $this->send_ak,
            'message' => urlencode($msg),
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->test_url, $post_data, $this->header, [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }


}
