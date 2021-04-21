<?php

namespace App\HttpController\Service\HuoYan;

use App\HttpController\Service\Common\CommonService;
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
    function getData($data)
    {
        $url = CreateConf::getInstance()->getConf('huoyan.url');

        $postData = [
            'com' => $data['com'],
            'keyword' => $data['keyword'],
            'tag' => $data['tag'],
            'province' => $data['province'],
            'financing' => $data['financing'],
            'time' => $data['time'],
            'page' => $data['page'],
        ];

        $res = (new CoHttpClient())->useCache(false)->send($url, $postData, $this->sendHeaders);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
