<?php

namespace App\HttpController\Service\LiuLengJing;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\utils\str;

class LiuLengJingService extends ServiceBase
{
    use Singleton;

    private $baseUrl;
    private $AppId;
    private $Ak;
    private $Sk;
    private $Token;
    private $Pem;

    function __construct()
    {
        $this->baseUrl = 'http://open.linkinip.com/apis/';
        $this->AppId = CreateConf::getInstance()->getConf('liulengjing.AppId');
        $this->Ak = CreateConf::getInstance()->getConf('liulengjing.Ak');
        $this->Sk = CreateConf::getInstance()->getConf('liulengjing.Sk');
        $this->Token = CreateConf::getInstance()->getConf('liulengjing.Token');
        $this->Pem = implode(PHP_EOL, CreateConf::getInstance()->getConf('liulengjing.pem'));
        return parent::__construct();
    }

    private function createParams(array $params, string $method, string $version = '1.0'): array
    {
        $body = [
            'app_id' => $this->AppId,
            'method' => $method,
            'version' => $version,
            'charset' => 'UTF-8',
            'timestamp' => \Carbon\Carbon::now()->format('Y-m-d H:i:s'),
            'app_auth_token' => $this->Token,
            'sign_type' => 'RSA2',
        ];

        return empty($params) ? $body : array_merge($params, $body);
    }

    private function createSign(array $body): string
    {
        ksort($body);
        $bodyStr = '';
        foreach ($body as $key => $val) {
            $bodyStr .= "{$key}={$val}&";
        }

        $pkeyid = openssl_pkey_get_private($this->Pem);
        $verify = openssl_sign(trim($bodyStr, '&'), $signature, $pkeyid, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    //中国专利著录项目数据
    function patentCnBasics(array $arr): ?array
    {
        $params = $this->createParams($arr, 'patent.cn.basics');
        $params['sign'] = $this->createSign($params);

        $res = (new CoHttpClient())->useCache(false)->send($this->baseUrl, $params);

        return is_string($res) ? jsonDecode($res) : $res;
    }

}
