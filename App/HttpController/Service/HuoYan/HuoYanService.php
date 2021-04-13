<?php

namespace App\HttpController\Service\HuoYan;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class HuoYanService extends ServiceBase
{
    private $sendHeaders;

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function __construct()
    {
        $this->sendHeaders = [
            'content-type' => 'application/x-www-form-urlencoded',
            'authorization' => CreateConf::getInstance()->getConf('huoyan.token')
        ];

        return parent::__construct();
    }

    //整理请求结果
    private function checkResp($res)
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['error'] == 0 ? $res['code'] = 200 : $res['code'] = $res['error'];

        isset($res['data']['total']) ? $res['Paging']['total'] = $res['data']['total'] - 0 : $res['Paging'] = null;

        isset($res['data']['lists']) ? $res['Result'] = $res['data']['lists'] : $res['Result'] = null;

        $res['Message'] = $res['msg'];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['Message']);
    }

    //仿企名片
    function getData($postData)
    {
        $url = CreateConf::getInstance()->getConf('huoyan.url');

        $postData = [
            'tag' => '物联网 硬件',
            'page' => '1',
            'province' => '北京',
            'financing' => 'A轮',
            'time' => '3-4'
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $postData, $this->sendHeaders);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
