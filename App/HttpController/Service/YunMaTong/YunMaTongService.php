<?php

namespace App\HttpController\Service\YunMaTong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

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

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg']);
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

        CommonService::getInstance()->log4PHP([
            'info' => '发送前',
            'postData' => $postData,
            'body' => $body,
        ]);

        return $body;
    }

    function bankCardInfo($bankcard)
    {
        $url = $this->url . '?bizorderno=' . $this->requestsn;

        $body = $this->createRequestData(['bankcard' => $bankcard]);

        $res = (new CoHttpClient())->useCache(false)->send($url, $body, [], [], 'postjson');

        CommonService::getInstance()->log4PHP([
            'info' => '发送后',
            'res' => $res,
        ]);

        $res = base64_decode($res);

        CommonService::getInstance()->log4PHP([
            'info' => 'base64_decode',
            'base64_decode' => $res,
        ]);

        openssl_private_decrypt($res, $decrypted_data, $this->privateKey);

        CommonService::getInstance()->log4PHP([
            'info' => 'openssl_private_decrypt',
            'openssl_private_decrypt' => $decrypted_data,
        ]);

        $decrypted_data = urldecode($decrypted_data);

        CommonService::getInstance()->log4PHP([
            'info' => 'urldecode',
            'urldecode' => $decrypted_data,
        ]);

        $decrypted_data = jsonDecode($decrypted_data);

        CommonService::getInstance()->log4PHP([
            'info' => 'jsonDecode',
            'jsonDecode' => $decrypted_data,
        ]);
    }


}
