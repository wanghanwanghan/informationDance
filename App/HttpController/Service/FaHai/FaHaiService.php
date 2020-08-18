<?php

namespace App\HttpController\Service\FaHai;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class FaHaiService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $authCode;
    private $rt;

    function __construct()
    {
        $this->authCode = \Yaconf::get('fahai.authCode');
        $this->rt = time() * 1000;
    }

    function getList($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);
        $doc_type = $body['doc_type'];
        $keyword = $body['keyword'];
        $pageno = $body['pageno'];
        $range = $body['range'];

        $json_data = [
            'dataType' => $doc_type,
            'keyword' => $keyword,
            'pageno' => $pageno,
            'range' => $range
        ];

        $json_data = json_encode($json_data);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'args' => $json_data
        ];

        return (new CoHttpClient())->send($url, $data);
    }

    function getDetail($url, $body)
    {
        $sign_num = md5($this->authCode . $this->rt);

        $data = [
            'authCode' => $this->authCode,
            'rt' => $this->rt,
            'sign' => $sign_num,
            'id' => $body['id']
        ];

        return (new CoHttpClient())->send($url, $data);
    }

}
