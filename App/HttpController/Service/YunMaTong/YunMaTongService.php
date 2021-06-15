<?php

namespace App\HttpController\Service\YunMaTong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\utils\arr;

class YunMaTongService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $key;
    private $url;
    private $bizno;
    private $publicKey;
    private $privateKey;
    private $requestsn;

    function __construct()
    {
        $this->key = CreateConf::getInstance()->getConf('yunmatong.key');
        $this->url = CreateConf::getInstance()->getConf('yunmatong.url');
        $this->bizno = CreateConf::getInstance()->getConf('yunmatong.bizno');
        $this->publicKey = CreateConf::getInstance()->getConf('yunmatong.publicKey');
        $this->privateKey = CreateConf::getInstance()->getConf('yunmatong.privateKey');
        $this->requestsn = control::getUuid();

        return parent::__construct();
    }

    private function checkResp($res)
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

    private function handleResp($res): ?array
    {
        $crypto = '';
        foreach (str_split(base64_decode($res), 128) as $chunk) {
            openssl_private_decrypt($chunk, $decrypted, implode(PHP_EOL, $this->privateKey));
            $crypto .= $decrypted;
        }

        return jsonDecode(urldecode($crypto));
    }

    private function createRequestData($postData): array
    {
        $postData['key'] = $this->key;
        $postData['bizno'] = $this->bizno;
        $postData['requestsn'] = $this->requestsn;
        $postData['requesttime'] = Carbon::now()->format('YmdHis');
        $crypto = '';
        foreach (str_split(json_encode($postData), 117) as $chunk) {
            openssl_public_encrypt($chunk, $encrypted, implode(PHP_EOL, $this->publicKey));
            $crypto .= $encrypted;
        }
        $body['body'] = $this->bizno . base64_encode($crypto);

        return $body;
    }

    function bankCardInfo($bankcard)
    {
        $url = $this->url . '?bizorderno=' . $this->requestsn;

        $body = $this->createRequestData(['bankcard' => $bankcard]);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)->send($url, $body);

        return $this->checkResp($this->handleResp($res));
    }


}
