<?php

namespace App\HttpController\Service\HuiCheJian;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\utils\arr;

class HuiCheJianService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    //整理请求结果
    private function checkResp($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['error'] == 0 ? $res['code'] = 200 : $res['code'] = $res['error'];

        isset($res['data']['total']) ? $res['Paging']['total'] = $res['data']['total'] - 0 : $res['Paging'] = null;

        isset($res['data']['lists']) ? $res['Result'] = $res['data']['lists'] : $res['Result'] = null;

        $res['Message'] = $res['msg'];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['Message']);
    }

    function getAuthPdf($data): array
    {
        $url = CreateConf::getInstance()->getConf('huichejian.getPdfUrl');
        $appId = CreateConf::getInstance()->getConf('huichejian.appId');

        $postData = [
            'entName' => $data['entName'],
            'socialCredit' => $data['socialCredit'],
            'legalPerson' => $data['legalPerson'],
            'idCard' => $data['idCard'],
            'phone' => $data['phone'],
            'region' => $data['region'],
            'address' => $data['address'],
            'requestId' => $data['requestId'],
        ];

        CommonService::getInstance()->log4PHP($postData);

        $pub = <<<Eof
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAsMhqc9x/Hm1ZArfiA+vB
+tUpaHgKFgAeJo3R7iVZliYbWtCb14W71aMCTUJiMbHjWxB94q6OYS52c+q9nAPn
GKvuVuTdTxc4SfX28KpGGosN5TXQLXklUCt6/mZQwwkJzzgA+C+iuggjo+VkRbfS
azmws59YP9+MXtORObFhCmxZIJ4ux7UkI3IjNCDgKUqe7hy6TJHSye0A8r2f/YqL
ZCZQKcdlw8WqoGNfGu8BWQnCBE3D6lKb5waLoLmK0vmU36W0y7vHQt1vfgVyr6qt
mkZMyQeljfHWcne3WwOxYrzKPfa0i64GDWTQdJB/lUxGKvUpc4e0x9nOpSlwxQN0
QQIDAQAB
-----END PUBLIC KEY-----
Eof;
        $aes_key = control::getUuid();

        $content = control::aesEncode(jsonEncode($postData), $aes_key, 256);

        $post_data = [
            'encrypt' => control::rsaEncrypt($aes_key, $pub),
            'content' => $content,
        ];

        CommonService::getInstance()->log4PHP($post_data);

        $res = (new CoHttpClient())->useCache(false)
            ->send($url, $post_data, [], [], 'postjson');

        CommonService::getInstance()->log4PHP($res);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
