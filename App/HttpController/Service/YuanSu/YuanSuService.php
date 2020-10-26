<?php

namespace App\HttpController\Service\YuanSu;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class YuanSuService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appId;
    private $appKey;

    function __construct()
    {
        $this->appId = CreateConf::getInstance()->getConf('yuansu.appIdTest');
        $this->appKey = CreateConf::getInstance()->getConf('yuansu.appKeyTest');

        return parent::__construct();
    }

    private function getTimestamp()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    private function checkResp($res)
    {
        //这里还没改好
        if (isset($res['PAGEINFO']) && isset($res['PAGEINFO']['TOTAL_COUNT']) && isset($res['PAGEINFO']['TOTAL_PAGE']) && isset($res['PAGEINFO']['CURRENT_PAGE'])) {
            $res['Paging'] = [
                'page' => $res['PAGEINFO']['CURRENT_PAGE'],
                'pageSize' => null,
                'total' => $res['PAGEINFO']['TOTAL_COUNT'],
                'totalPage' => $res['PAGEINFO']['TOTAL_PAGE'],
            ];
        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['code'] == '000' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res['data']) ? $res['Result'] = $res['data'] : $res['Result'] = [];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    function getList($url, $body)
    {
        $body = jsonEncode($body);

        $requestSn = control::getUuid();

        $timestamp = $this->getTimestamp();

        $sign = md5($requestSn . $timestamp . $body . $this->appKey);

        $header = [
            'app-id' => $this->appId,
            'timestamp' => $timestamp,
            'signature' => $sign,
            'request-sn' => $requestSn
        ];

        $res = (new CoHttpClient())->send($url, jsonDecode($body), $header, [], 'postJson');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
