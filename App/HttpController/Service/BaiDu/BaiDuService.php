<?php

namespace App\HttpController\Service\BaiDu;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class BaiDuService extends ServiceBase
{
    use Singleton;

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $clientId;
    private $clientSecret;
    private $checkWordUrl;

    private $clientIdForOrc;
    private $clientSecretForOcr;
    private $ocrUrl;

    private $getTokenUrl;

    function __construct()
    {
        $this->clientId = CreateConf::getInstance()->getConf('baidu.clientId');
        $this->clientSecret = CreateConf::getInstance()->getConf('baidu.clientSecret');
        $this->checkWordUrl = CreateConf::getInstance()->getConf('baidu.checkWordUrl');
        $this->clientIdForOrc = CreateConf::getInstance()->getConf('baidu.clientIdForOrc');
        $this->clientSecretForOcr = CreateConf::getInstance()->getConf('baidu.clientSecretForOcr');
        $this->ocrUrl = CreateConf::getInstance()->getConf('baidu.ocrUrl');
        $this->getTokenUrl = CreateConf::getInstance()->getConf('baidu.getTokenUrl');
        return parent::__construct();
    }

    //获取token
    private function getToken($client_id, $client_secret)
    {
        $res = (new CoHttpClient())->needJsonDecode(true)->send($this->getTokenUrl, [
            'grant_type' => 'client_credentials',
            'client_id' => $client_id,
            'client_secret' => $client_secret
        ], [], 'get');

        return $res['access_token'];
    }

    //百度内容审核 - 纯文本
    function checkWord($content)
    {
        //https://login.bce.baidu.com/?account=&redirect=http%3A%2F%2Fconsole.bce.baidu.com%2F%3Ffromai%3D1#/aip/overview

        $label = [
            0 => '绝对没有',
            1 => '暴恐违禁',
            2 => '文本色情',
            3 => '政治敏感',
            4 => '恶意推广',
            5 => '低俗辱骂',
            6 => '低质灌水'
        ];

        $token = $this->getToken($this->clientId, $this->clientSecret);

        $url = $this->checkWordUrl . "?access_token={$token}";

        $postData = ['content' => $content];

        $res = (new CoHttpClient())->needJsonDecode(true)->send($url, $postData);

        $res = obj2Arr($res);

        //reject里是敏感词信息
        if (!empty($res) && isset($res['result']) && isset($res['result']['reject']) && !empty($res['result']['reject'])) {
            //如果有敏感词汇就替换
            foreach ($res['result']['reject'] as $reject) {
                foreach ($reject['hit'] as $one) {
                    $content = str_replace([$one], '***', $content);
                }
            }
        }

        //review里是 涉嫌 敏感词信息
        if (!empty($res) && isset($res['result']) && isset($res['result']['review']) && !empty($res['result']['review'])) {
            //如果有敏感词汇就替换
            foreach ($res['result']['review'] as $reject) {
                foreach ($reject['hit'] as $one) {
                    $content = str_replace([$one], '???', $content);
                }
            }
        }

        return $content;
    }

    //百度ocr提取图片中文字
    function ocr($file)
    {
        $token = $this->getToken($this->clientIdForOrc, $this->clientSecretForOcr);

        $url = $this->ocrUrl . "?access_token={$token}";

        $headers = ['Content-Type' => 'application/x-www-form-urlencoded'];

        $postData = [
            'image' => base64_encode($file)
        ];

        $res = (new CoHttpClient())->send($url, $postData, $headers);

        return $res;
    }

}
