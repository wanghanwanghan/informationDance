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
        parent::__construct();
        $this->appId = CreateConf::getInstance()->getConf('yuansu.appId');
        $this->appKey = CreateConf::getInstance()->getConf('yuansu.appKey');
    }

    private function getTimestamp()
    {
        list($s1, $s2) = explode(' ', microtime());

        return (float)sprintf('%.0f', (floatval($s1) + floatval($s2)) * 1000);
    }

    private function checkResp($res, $docType, $type = 'list')
    {
        $type = ucfirst($type);

        if (isset($res['pageNo']) && isset($res['range']) && isset($res['totalCount']) && isset($res['totalPageNum'])) {
            $res['Paging'] = [
                'page' => $res['pageNo'],
                'pageSize' => $res['range'],
                'total' => $res['totalCount'],
                'totalPage' => $res['totalPageNum'],
            ];

        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['code'] === 's' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        if ($type === 'List') {
            isset($res[$docType . $type]) ? $res['Result'] = $res[$docType . $type] : $res['Result'] = [];
        } else {
            isset($res[$docType]) ? $res['Result'] = $res[$docType] : $res['Result'] = [];
        }

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
    }

    function getList($url, $body)
    {
        $body = json_encode($body);

        $requestSn = control::getUuid();

        $timestamp = $this->getTimestamp();

        $sign = md5($requestSn . $timestamp . $body . $this->appKey);

        $header = [
            'app-id' => $this->appId,
            'timestamp' => $timestamp,
            'signature' => $sign,
            'request-sn' => $requestSn
        ];

        $res = (new CoHttpClient())->send($url, json_decode($body, true), $header,[],'postJson');

        var_dump($res);

    }





}
