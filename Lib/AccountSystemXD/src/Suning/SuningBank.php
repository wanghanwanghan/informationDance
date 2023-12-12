<?php

namespace AccountSystemXD\Suning;

use AccountSystemXD\Helper\Helper;
use AccountSystemXD\Helper\Sm4;
use AccountSystemXD\Suning\Traits\SuningBankT;

class SuningBank
{
    use SuningBankT;

    private static $instance;

    private $serAppPem = null;
    private $cliAppPem = null;

    private $certPem = null;
    private $privatePem = null;
    private $sslPwd = null;

    private $urlBase = null;

    private $appCode = '91110108MA01KPGK0L0002';
    private $merchantId = 'MRXD0002';
    private $channelId = 'MRXD0001';
    private $platformcd = '80355';
    private $algorithm = 'SHA256withRSA';
    private $ipAddress = '39.105.35.154';
    private $terminal = '1';

    private $header = [];

    private $sendData = null;

    static function getInstance(...$args): SuningBank
    {
        if (!isset(self::$instance)) {
            self::$instance = new static(...$args);
        }

        return self::$instance;
    }

    // 生成secretKey
    private function createSecretKey(): array
    {
        $ori = md5(Helper::getInstance()->getMicroTime());
        // 苏宁端加密公钥
        $publicKey = openssl_get_publickey($this->serAppPem);
        openssl_public_encrypt($ori, $encrypted, $publicKey);
        return ['ori' => $ori, 'secret' => bin2hex($encrypted)];
    }

    // 解密secretKey
    private function decryptSecretKey(string $sk): string
    {
        $keyStr = $this->cliAppPem;
        $privateKey = openssl_get_privatekey($keyStr);
        openssl_private_decrypt(hex2bin($sk), $base, $privateKey);
        return $base;
    }

    // 生成signature
    private function createSignature(array $params): string
    {
        // 传文件一张一张传 file_0123 不参与加签 这地方先这样
        unset($params['file_0'], $params['file_1'], $params['file_2'], $params['file_3']);

        // 合并顺序 转换格式 urlencode 类型 不参与签名
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                ksort($v);
                $params[$k] = http_build_query($v);
            }
        }

        ksort($params);

        // 得到签名原始字符串 明文
        $ori = http_build_query($params);

        // 签名私钥加密 输出16进制
        $pkeyid = openssl_pkey_get_private($this->cliAppPem);
        openssl_sign($ori, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
        return bin2hex($signature);
    }

    // 生成最终发送给苏宁的参数 尽量保持payload里没有空值
    function setParams(array $payload, array $public): SuningBank
    {
        $secret = $this->createSecretKey();

        // 去掉第一层空值
        $payload = Helper::getInstance()->arrayFilter($payload);

        // sm4加密
        $payloadSM4 = (new Sm4())
            ->setKey($secret['ori'])
            ->encryptData(Helper::getInstance()->jsonEncode($payload));

        // 去掉第一层空值
        $public = Helper::getInstance()->arrayFilter($public);

        Helper::getInstance()->writeLog(array_merge($payload, $public));

        // 补全其他 公共参数
        $public['appCode'] = $this->appCode;
        $public['timestamp'] = date('Y-m-d H:i:s');
        $public['algorithm'] = $this->algorithm;
        $public['terminal'] = $this->terminal;
        $public['ipAddress'] = $this->ipAddress;
        $public['secretKey'] = $secret['secret'];

        // 签名
        $signature = $this->createSignature(array_merge($payload, $public));

        $sendData = [];
        $sendData['payload'] = $payloadSM4;
        $sendData['signature'] = $signature;
        $this->sendData = array_merge($sendData, $public);

        Helper::getInstance()->writeLog($this->sendData);

        return $this;
    }

    // 发送http请求 回头改成coHttpCli
    function send(string $transCode): array
    {
        $url = trim($this->urlBase, '/') . "/{$this->appCode}/{$transCode}";

        $curl = curl_init(); //初始化
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $this->header); //设置请求header
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); // 几秒后没链接上就自动断开
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算法是否存在
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //返回值不直接显示
        curl_setopt($curl, CURLOPT_POSTFIELDS, $this->sendData); //提交的数据
        curl_setopt($curl, CURLOPT_HEADER, false); //不输出响应头
        curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM'); // PHP的 CURL 只支持 PEM 方式
        curl_setopt($curl, CURLOPT_SSLCERT, $this->certPem); // cert.pem文件路径
        curl_setopt($curl, CURLOPT_SSLCERTPASSWD, 'wanghan123'); // 证书密码
        curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM'); // PHP的 CURL 只支持 PEM 方式
        curl_setopt($curl, CURLOPT_SSLKEY, $this->privatePem); // private.pem 文件路径
        curl_setopt($curl, CURLOPT_VERBOSE, false); // 输出详细调试信息
        $res = curl_exec($curl); //发送请求
        $error = curl_error($curl);
        curl_close($curl);

        Helper::getInstance()->writeLog(['result' => $res, 'error' => $error]);

        return ['result' => $res, 'error' => $error];
    }

    // 苏宁返回的报文解秘
    function handlerPayload($result)
    {
        $info = is_array($result) ? $result : Helper::getInstance()->jsonDecode($result);

        $secretKey = $this->decryptSecretKey($info['secretKey']);

        // sm4解密
        $payload = trim((new Sm4())->setKey($secretKey)->decryptData($info['payload']));

        return Helper::getInstance()->jsonDecode($payload);
    }

    // 什么破玩意
    function setHeader(string $version): SuningBank
    {
        $this->header = [
            'charset: UTF-8',
            'content-type: multipart/form-data',
            "version: {$version}",
            'appCode: 91110108MA01KPGK0L0002',
        ];
        return $this;
    }

    function __get(string $name)
    {
        return $this->$name;
    }

}