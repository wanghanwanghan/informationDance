<?php

namespace App\HttpController\Service\TaoShu;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class TaoShuService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $uid;
    private $url;
    private $taoshuPEM;

    function __construct(...$args)
    {
        parent::__construct();

        $this->uid = CreateConf::getInstance()->getConf('taoshu.uid');
        $this->url = CreateConf::getInstance()->getConf('taoshu.baseUrl');
        $this->taoshuPEM = implode(PHP_EOL, CreateConf::getInstance()->getConf('taoshu.pem'));

        if (!empty($args)) {
            $this->uid = $args[0];
            $this->url = $args[1];
            $this->taoshuPEM = $args[2];
        }

        openssl_get_publickey($this->taoshuPEM);
    }

    private function authCode($string, $operation = 'DECODE', $key = '', $expiry = 0)
    {
        $ckey_length = 4;

        $keya = md5(substr($key, 0, 16));
        $keyb = md5(substr($key, 16, 16));
        $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length) : substr(md5(microtime()), -$ckey_length)) : '';

        $cryptkey = $keya . md5($keya . $keyc);
        $key_length = strlen($cryptkey);

        $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) : sprintf('%010d', $expiry ? $expiry + time() : 0) . substr(md5($string . $keyb), 0, 16) . $string;
        $string_length = strlen($string);

        $result = '';
        mt_srand();
        $box = range(0, 255);

        $rndkey = [];
        for ($i = 0; $i <= 255; $i++) {
            $rndkey[$i] = ord($cryptkey[$i % $key_length]);
        }

        for ($j = $i = 0; $i < 256; $i++) {
            $j = ($j + $box[$i] + $rndkey[$i]) % 256;
            $tmp = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }

        for ($a = $j = $i = 0; $i < $string_length; $i++) {
            $a = ($a + 1) % 256;
            $j = ($j + $box[$a]) % 256;
            $tmp = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));
        }

        if ($operation == 'DECODE') {
            if ((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26) . $keyb), 0, 16)) {
                return substr($result, 26);
            } else {
                return '';
            }
        } else {
            return $keyc . str_replace('=', '', base64_encode($result));
        }
    }

    private function quantumEncode($source, $key)
    {
        $result = [];
        $random = md5(time());
        $source = urlencode($source);
        //des 加密
        $value = $this->authCode($source, 'ENCODE', $random);
        $result['value'] = $value;
        //rsa加密
        $encrypt = '';
        openssl_public_encrypt($random, $encrypt, $key);
        $encrypt = base64_encode($encrypt);
        $result['key'] = $encrypt;
        return $result;
    }

    private function quantumDecode($result, $key)
    {
        $_key = $result->key;
        $_value = $result->value;
        $_key = base64_decode($_key);
        //rsa解密
        $decrypt = '';
        openssl_public_decrypt($_key, $decrypt, $key);
        //des解密
        $_value = mb_convert_encoding($_value, 'UTF-8', 'ASCII');
        $_value = $this->authCode($_value, 'DECODE', $decrypt);
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

        $postBodyJson = $this->quantumEncode(jsonEncode($postBody), $this->taoshuPEM);

        //参数固定格式
        $p_arr['uid'] = $this->uid;
        $p_arr['data'] = jsonEncode($postBodyJson);

        //options
        $options = [
            'useThisKey' => $this->useThisKey($body, $service)
        ];

        $data = (new CoHttpClient())->needJsonDecode(false)->send($this->url, $p_arr, $header, $options, 'post');

        $data = urldecode($data);

        $rs = $this->quantumDecode(json_decode($data), $this->taoshuPEM);

        $rs = jsonDecode($rs);
        CommonService::getInstance()->log4PHP([$postBody,$data,$rs],'info','taoshu_post_ret');

        return $this->checkRespFlag ? $this->checkResp($rs) : $rs;
    }

    private function checkResp($res)
    {
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

        $res['ISUSUAL'] == '1' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res['RESULTDATA']) ? $res['Result'] = $res['RESULTDATA'] : $res['Result'] = [];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], null);
    }


}
