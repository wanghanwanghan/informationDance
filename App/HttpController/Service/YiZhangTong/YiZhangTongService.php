<?php

namespace App\HttpController\Service\YiZhangTong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class YiZhangTongService extends ServiceBase
{
    private $test_url;
    private $test_app_id;
    private $test_channel;
    private $test_service_id;
    private $test_app_secret;
    private $test_rsa_pub;

    function __construct()
    {
        parent::__construct();
        $this->test_url = 'https://smelp-wg-web-stg1.ocft.com/smelp-wg/smelp_wg';
        $this->test_app_id = 'APP_1400';
        $this->test_channel = '1009';
        $this->test_service_id = 'serviceId字典值';
        $this->test_app_secret = 'dHKq/fx1/7F9lUWkHhv1Dw==';
        $this->test_rsa_pub = <<<str
-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCZKs+rCLRSPeS0ioV9lLDztKF3
8INbwuKt4U5YrbG0kg7O9KxyDaA2X2OtwO2YZDZ5S71/bgGyxaHbmdwzEuEJT0iy
7St8/U609nOQuRZsYtAsWfkCesjiXDJUk54ZhlZwo6NxeBRfhgJnwz/772DusCEu
tv6KR7pT0nAqXHIyXQIDAQAB
-----END PUBLIC KEY-----
str;
    }

    private function checkResp($res): array
    {
        $code = 200;


        $paging = null;


        $result = null;


        $msg = null;

        return $this->createReturn($code, $paging, $result, $msg);
    }

    function getDptEntDetail($entname = '', $creditcode = '', $licreccode = '')
    {
        $post_data = [
            'entname' => trim($entname),
            'creditcode' => trim($creditcode),
            'licreccode' => trim($licreccode),
        ];

        $post_data = array_filter($post_data);

        $url = $this->test_url . '/dpt/ent/detail';

        $resp = (new CoHttpClient())->send($url, $post_data, [], [], 'get');

        CommonService::getInstance()->log4PHP($resp);

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

}
