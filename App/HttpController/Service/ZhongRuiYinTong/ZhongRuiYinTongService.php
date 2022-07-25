<?php

namespace App\HttpController\Service\ZhongRuiYinTong;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class ZhongRuiYinTongService extends ServiceBase
{
    private $urlBase = 'http://222.128.37.11:2212/';

    function __construct()
    {
        $this->key = CreateConf::getInstance()->getConf('yunmatong.key');
        $this->bizno = CreateConf::getInstance()->getConf('yunmatong.bizno');
        $this->publicKey = CreateConf::getInstance()->getConf('yunmatong.publicKey');
        $this->privateKey = CreateConf::getInstance()->getConf('yunmatong.privateKey');
        $this->requestsn = control::getUuid();

        return parent::__construct();
    }

    // 通过用户名密码获取访问接口所需要的 token 信息
    private function getToken(string $username, string $password): ?string
    {
        $url = $this->urlBase . 'api/system/login';

        $postData = [
            'username' => $username,
            'password' => $password,
        ];

        $login_info = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $postData);

    }

    private function checkResp($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['paging'], [], 'co请求错误');

        if (isset($res['errcode']) && $res['errcode'] - 0 === 0) {
            $res['code'] = 200;
        } else {
            $res['code'] = $res['errcode'];
        }

        $res['paging'] = null;

        if (isset($res['result']['consumestate'])) unset($res['result']['consumestate']);
        if (isset($res['result']['consumemoney'])) unset($res['result']['consumemoney']);
        if (isset($res['result']['photo'])) unset($res['result']['photo']);
        if (isset($res['result']['areaid'])) unset($res['result']['areaid']);
        if (isset($res['result']['areaname'])) unset($res['result']['areaname']);
        if (isset($res['result']['provincename'])) unset($res['result']['provincename']);

        return $this->createReturn($res['code'], $res['paging'], $res['result'], $res['message']);
    }


}
