<?php

namespace App\HttpController\Service\DaTong;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class DaTongService extends ServiceBase
{
    private $base_url = 'https://api.biaoxun.cn/api/search/find';
    private $accessKey;
    private $secretKey;

    function __construct()
    {
        $this->accessKey = CreateConf::getInstance()->getConf('datong.ztb_accessKey');
        $this->secretKey = CreateConf::getInstance()->getConf('datong.ztb_secretKey');

        return parent::__construct();
    }

    private function checkResp($res): array
    {
        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    function getList(array $data)
    {
        $randomStr = control::getUuid();
        $time = microTimeNew();

        $sign = md5($this->accessKey . $time . $randomStr . $this->secretKey);

        $header = [
            'key' => $this->accessKey,
            'timestamp' => $time,
            'randomStr' => $randomStr,
            'sign' => $sign,
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->base_url, $data, $header, [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }


}
