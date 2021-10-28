<?php

namespace App\HttpController\Service\LiuLengJing;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

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

    private function check(array $arr, string $type): array
    {
        CommonService::getInstance()->log4PHP($arr);

        $ret_key = '';

        for ($i = 0; $i < strlen($type); $i++) {
            if (ord($type[$i]) >= ord('A') && ord($type[$i]) <= ord('Z')) {
                $ret_key .= '_' . strtolower($type[$i]);
            } else {
                $ret_key .= $type[$i];
            }
        }

        $ret_key .= '_response';

        $total = (isset($arr[$ret_key]['total']) && is_numeric($arr[$ret_key]['total'])) ?
            $arr[$ret_key]['total'] - 0 :
            null;

        $ret = (isset($arr[$ret_key]['records'])) ? $arr[$ret_key]['records'] : null;

        return $this->createReturn($arr[$ret_key]['code'] - 0, ['total' => $total], $ret, $arr[$ret_key]['msg']);
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

        $res = is_string($res) ? jsonDecode($res) : obj2Arr($res);

        return $this->checkRespFlag ? $this->check($res, __FUNCTION__) : $res;
    }

    //中国专利评分估值数据_结果
    function patentCnIndexHit(array $arr): ?array
    {
        $params = $this->createParams($arr, 'patent.cn.index.hit');
        $params['sign'] = $this->createSign($params);

        $res = (new CoHttpClient())->useCache(false)->send($this->baseUrl, $params);

        $res = is_string($res) ? jsonDecode($res) : obj2Arr($res);

        return $this->checkRespFlag ? $this->check($res, __FUNCTION__) : $res;
    }

}
