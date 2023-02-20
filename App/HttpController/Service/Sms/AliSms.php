<?php

namespace App\HttpController\Service\Sms;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\Task\Service\TaskService;
use Carbon\Carbon;
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
                    'access_key_id' => CreateConf::getInstance()->getConf('env.aliAk'),
                    'access_key_secret' => CreateConf::getInstance()->getConf('env.aliSk'),
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

    function comm($phone, $code): bool
    {
        $easySms = $this->createObj();

        TaskService::getInstance()->create(function () use ($easySms, $phone, $code) {
            return $easySms->send($phone, [
                'template' => 'SMS_218160347',
                'data' => [
                    'code' => $code
                ],
            ]);
        });

        return true;
    }

    function sendByTemplete($phone, $template,$data): bool
    {
        $easySms = $this->createObj();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'sendByTemplete start'
            ])
        );
        $res = TaskService::getInstance()->create(function () use ($easySms, $phone, $template,$data) {
            $res = $easySms->send($phone, [
                'template' =>  $template,
                'data' => $data,
            ]);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'sendByTemplete $res' => $res
                ])
            );
            return $res;
        });

        return true;
    }

    function sendByTempleteV2($phone, $template,$data)
    {
        $easySms = $this->createObj();
        CommonService::getInstance()->log4PHP(
            json_encode([
                '验证码-发送-开始' => [
                    '手机' => $phone,
                    '模板' => $template,
                    '数据' => $data,
                ],
            ],JSON_UNESCAPED_UNICODE)
        );

        $res = TaskService::getInstance()->create(function () use ($easySms, $phone, $template,$data) {
            $res = $easySms->send($phone, [
                'template' =>  $template,
                'data' => $data,
            ]);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '验证码-发送-结果' => [
                        '手机' => $phone,
                        '模板' => $template,
                        '数据' => $data,
                        '$res' => $res,
                    ],
                ],JSON_UNESCAPED_UNICODE)
            );
            return $res;
        });

        return $res;
    }

    function sendByTempleteV3($phone, $template,$data)
    {
        $easySms = $this->createObj();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'sendByTemplete start'
            ])
        );
        $res = $easySms->send($phone, [
            'template' =>  $template,
            'data' => $data,
        ]);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'sendByTemplete $res' => $res
            ])
        );
        return $res;
    }

    function reg($phone, $code): bool
    {
        $easySms = $this->createObj();

        TaskService::getInstance()->create(function () use ($easySms, $phone, $code) {
            return $easySms->send($phone, [
                'template' => 'SMS_212930320',
                'data' => [
                    'code' => $code
                ],
            ]);
        });

        return true;
    }

    function login($phone, $code): bool
    {
        $easySms = $this->createObj();

        TaskService::getInstance()->create(function () use ($easySms, $phone, $code) {
            return $easySms->send($phone, [
                'template' => 'SMS_212930322',
                'data' => [
                    'code' => $code
                ],
            ]);
        });

        return true;
    }

    function afterUploadAuthBook($phone, $ext): bool
    {
        $easySms = $this->createObj();

        TaskService::getInstance()->create(function () use ($easySms, $phone, $ext) {
            return $easySms->send($phone, [
                'template' => 'SMS_214830863',
                'data' => [
                    'name' => '尊敬的管理员',
                    'time' => Carbon::now()->format('Y-m-d H:i:s')
                ],
            ]);
        });

        return true;
    }


}
