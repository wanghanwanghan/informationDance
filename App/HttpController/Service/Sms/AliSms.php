<?php

namespace App\HttpController\Service\Sms;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Strategies\OrderStrategy;

class AliSms
{
    public $conf;

    function __construct()
    {
        $this->conf = [
            // HTTP 请求的超时时间（秒）
            'timeout' => 5.0,
            // 默认发送配置
            'default' => [
                // 网关调用策略，默认：顺序调用
                'strategy' => OrderStrategy::class,
                // 默认可用的发送网关
                'gateways' => [
                    'aliyun',
                ],
            ],
            // 可用的网关配置
            'gateways' => [
                'errorlog' => [
                    'file' => LOG_PATH . 'ali-sms.log',
                ],
                'aliyun' => [
                    'access_key_id' => CreateConf::getInstance()->create('env.aliAk'),
                    'access_key_secret' => CreateConf::getInstance()->create('env.aliSk'),
                    'sign_name' => '每日信动',
                ],
                //...
            ],
        ];
    }

    private function createObj()
    {
        return new EasySms($this->conf);
    }

    function reg($phone, $code): bool
    {
        $easySms = $this->createObj();

        try {
            $easySms->send($phone, [
                'template' => 'SMS_212930320',
                'data' => [
                    'code' => $code
                ],
            ]);
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        }

        return true;
    }

    function login($phone, $code): bool
    {
        $easySms = $this->createObj();

        try {
            $easySms->send($phone, [
                'template' => 'SMS_212930322',
                'data' => [
                    'code' => $code
                ],
            ]);
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
            return false;
        }

        return true;
    }

}
