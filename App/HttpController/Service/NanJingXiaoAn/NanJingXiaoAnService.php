<?php

namespace App\HttpController\Service\NanJingXiaoAn;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\traits\Singleton;

class NanJingXiaoAnService extends ServiceBase
{
    use Singleton;

    private $url;
    private $xaKey;

    function __construct()
    {
        parent::__construct();
        $this->url = 'https://api.365dayservice.com:8044/credit-api/';
        $this->xaKey = CreateConf::getInstance()->getConf('nanjingxiaoan.xaKey');
    }

    private function checkResp($resp): array
    {
        if (isset($resp['coHttpErr']))
            return $this->createReturn(500, null, [], 'co请求错误');

        $code = $resp['code'] - 0;

        $code !== 2000 ?: $code = 200;

        $result = $resp['payload'] ?? '';

        $msg = trim($resp['message'] ?? '');

        return $this->createReturn($code, null, $result, $msg);
    }

    function generalMobileInfo(string $name, string $mobile): array
    {
        $name = trim($name);
        preg_match_all('/\d+/', $mobile, $phone);
        $phone = current(current($phone));

        if (empty($name) || !is_numeric($phone) || strlen($phone) !== 11) {
            return $this->createReturn(500, null, [], '姓名或电话错误');
        }

        $header = [
            'xa-key' => $this->xaKey,
        ];

        $options = [
            'enableSSL' => true,
        ];

        $resp = (new CoHttpClient())->useCache(false)->send(
            $this->url . 'v1/generalMobileInfo/2F_isp',
            ['name' => $name, 'mobile' => $mobile],
            $header,
            $options,
            'postjson'
        );

        //checkResult
        //1 一致
        //2 不一致
        //3 无此记录
        //1、2均计费
        //3 不计费

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

}
