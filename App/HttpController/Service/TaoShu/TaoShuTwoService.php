<?php

namespace App\HttpController\Service\TaoShu;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class TaoShuTwoService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $uid;
    private $url;
    private $taoshuPEM;

    function __construct()
    {
        parent::__construct();

        $this->uid = CreateConf::getInstance()->getConf('taoshu.industryUid');
        $this->url = CreateConf::getInstance()->getConf('taoshu.industryBaseUrl');
        $this->taoshuPEM = implode(PHP_EOL, CreateConf::getInstance()->getConf('taoshu.industryPem'));

        openssl_get_publickey($this->taoshuPEM);
    }

    private function enCode($source, $keyStr): array
    {
        $result = [];
        $random = md5(time());
        $source = urlencode($source);

        $result['value'] = base64_encode(openssl_encrypt($source, 'DES-ECB', $random, OPENSSL_RAW_DATA));

        $encrypt = '';
        openssl_public_encrypt($random, $encrypt, $keyStr);
        $encrypt = base64_encode($encrypt);
        $result['key'] = $encrypt;

        return $result;
    }

    private function deCode($result, $key): string
    {
        $_key = $result->key;
        $_value = $result->value;
        $_key = base64_decode($_key);

        $decrypt = '';
        openssl_public_decrypt($_key, $decrypt, $key);

        $_value = openssl_decrypt($_value, 'DES-ECB', $decrypt);

        return urldecode($_value);
    }

    function post($body, $service)
    {
        $header = [];

        $postBody = [
            'uid' => $this->uid,
            'service' => $service,
            'params' => $body
        ];

        $postBodyJson = $this->enCode(jsonEncode($postBody), $this->taoshuPEM);

        //参数固定格式
        $p_arr['uid'] = $this->uid;
        $p_arr['data'] = jsonEncode($postBodyJson);

        //$options
        $options = [
            'useThisKey' => $this->useThisKey($body, $service)
        ];

        $data = (new CoHttpClient())->needJsonDecode(false)
            ->useCache(false)
            ->send($this->url, $p_arr, $header, $options);

        $data = urldecode($data);

        $rs = $this->deCode(json_decode($data), $this->taoshuPEM);

        $rs = jsonDecode($rs);

        CommonService::getInstance()->log4PHP($rs);

        return $this->checkRespFlag ? $this->checkResp($rs) : $rs;
    }

    private function checkResp($res): array
    {
        $res['code'] = 200;
        $res['Paging'] = null;

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        //拿返回结果
        isset($res['RESULTDATA']) ? $res['Result'] = $res['RESULTDATA'] : $res['Result'] = [];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result']);
    }


}
