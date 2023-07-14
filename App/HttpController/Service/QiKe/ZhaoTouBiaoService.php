<?php

namespace App\HttpController\Service\QiKe;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ZhaoTouBiaoService extends ServiceBase
{
    private $base_url = 'https://openapi.qike366.com/';
    private $accessKey;
    private $secretKey;

    function __construct()
    {
        $this->accessKey = CreateConf::getInstance()->getConf('qike.ztb_accessKey');
        $this->secretKey = CreateConf::getInstance()->getConf('qike.ztb_secretKey');

        return parent::__construct();
    }

    private function checkResp($res): array
    {
        is_array($res) ?: $res = jsonDecode($res);

        $resp = [];
        $resp['code'] = 200;
        $resp['msg'] = '成功';
        $resp['paging']['totalPage'] = $res['pages'] ?? '';
        $resp['paging']['total'] = $res['total'] ?? '';
        $resp['result'] = $res['records'] ?? $res;

        return $this->createReturn($resp['code'], $resp['paging'], $resp['result'], $resp['msg']);
    }

    private function getToken()
    {
        $url = 'api/openapi/tokens/getToken';

        $data = [
            'accessKey' => $this->accessKey,
            'secretKey' => $this->secretKey
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->base_url . $url, $data, [], [], 'postjson');

        return jsonDecode($res);
    }

    function getList(string $keyword, int $page = 1, int $size = 50)
    {
        $info = $this->getToken();

        $url = 'open/data/bidding/list?access_token=' . $info['msg'];

        $data = [
            'keyword' => $keyword,
            'current' => $page,
            'size' => min($size, 100)
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->base_url . $url, $data, [], [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    function getDetail(string $mid)
    {
        $info = $this->getToken();

        $url = 'open/data/bidding/detail?access_token=' . $info['msg'];

        $data = [
            'mid' => trim($mid),
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->base_url . $url, $data, [], [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

}
