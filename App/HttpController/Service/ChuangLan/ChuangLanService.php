<?php

namespace App\HttpController\Service\ChuangLan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ChuangLanService extends ServiceBase
{
    public $appId;
    public $appKey;

    function __construct()
    {
        parent::__construct();
        $this->appKey = 'mUoCN8pT';
        $this->appId = 'Lz8AqXxJ';
        return true;
    }

    static function getStatusCnameMap(): array
    {
        return [
            1 => '正常',
            2 => '停机',
            3 => '在网但不可用',
            4 => '不在网',
            5 => '无短信能力',
            6 => '欠费',
            7 => '长时间关机',
            8 => '销号/未启用',
            9 => '服务器异常',
            10 => '查询失败',
        ];
    }

    private function checkResp($resp): array
    {
        if (isset($resp['coHttpErr']))
            return $this->createReturn(500, null, [], 'co请求错误');

        $code = $resp['code'] - 0;

        $code !== 200000 ?: $code = 200;

        $result = $resp ?? '';

        $msg = trim($resp['message'] ?? '');

        return $this->createReturn($code, null, $result, $msg);
    }

    /**
     * 号码状态检测
     * @param $param
     * @return array|mixed|string[]
     */
    function getCheckPhoneStatus($param)
    {
        $url = 'https://api.253.com/open/unn/batch-ucheck';

        $data = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'mobiles' => $param['mobiles'], // 检测手机号，多个手机号码用英文半角逗号隔开，仅支持国内号码
            'type' => 0,
        ];

        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data);
    }

    function mobileNetStatus($param)
    {
        $url = 'https://api.253.com/open/zwsjmd/mobile_netstatus';

        $data = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'mobile' => $param['mobile']
        ];

        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data);
    }

    function carriersTwoAuth(string $name, string $mobile)
    {
        $url = 'https://api.253.com/open/carriers/carriers-two-auth';

        $data = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'name' => trim($name),
            'mobile' => trim($mobile)
        ];

        $options = [
            'enableSSL' => true,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, [], $options, 'post');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }
}