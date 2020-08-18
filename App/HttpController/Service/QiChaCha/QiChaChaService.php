<?php

namespace App\HttpController\Service\QiChaCha;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class QiChaChaService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appkey;
    private $seckey;

    function __construct()
    {
        $this->appkey = \Yaconf::get('qichacha.appkey');
        $this->seckey = \Yaconf::get('qichacha.seckey');
    }

    //企查查全羁绊是get请求
    function get($url, $body)
    {
        $time = time();

        $token = strtoupper(md5($this->appkey . $time . $this->seckey));

        $header = ['Token' => $token, 'Timespan' => $time];

        $body['key'] = $this->appkey;

        $url .= '?' . http_build_query($body);

        return (new CoHttpClient())->send($url, $body, $header, [], 'get');
    }

}
