<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class wgetWBHjpg extends AbstractCronTask
{
    public $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每半小时执行一次
        return '*/30 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $openid = 'or9pL5Gl8PimHQRD7Ads2PojcDhw';
        $cookie = '__jsluid_s=5462f832218427d58180f026c6048014; sessionId=6f08787d3a874beaaf7c20c4283f0922; token=28c3e8b1f7e3473e95bf3965c442ef85';

        $info = $this->queryUserStatus($openid, $cookie);

        $check = false;

        if (!empty($info['result']['status']) && $info['result']['status'] === '1') {
            if (!empty($info['code']) && $info['code'] === '0000') {
                if (!empty($info['message']) && $info['message'] === '成功') {
                    $arr = $this->saveJpg($openid, $cookie);
                    if (!empty($arr['result']['data']) && substr($arr['result']['data']['qrcode_base64'], 0, 15) === 'data:image/jpeg') {
                        $check = true;
                        $stream = str_replace('data:image/jpeg;base64,', '', $arr['result']['data']['qrcode_base64']);
                        file_put_contents(IMAGE_PATH . 'wbh.jpg', base64_decode($stream));
                    }
                }
            }
        }

        if (!$check) {
            CommonService::getInstance()->log4PHP([$info, $arr ?? ''], 'info', 'wgetWBHjpg.log');
        }

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

    function saveJpg($openid, $cookie)
    {
        $url = 'https://wx.xwbank.com/api/ActivityMgm/partnerInviteQCode';
        $data = [
            'actId' => '1001049',
            'openId' => '1139518591313113089',
            'officialAccountAppid' => 'wxef424cc5a8cfb495',
            'customerSourceSceneId' => 'HG10035HQ',
        ];
        $sendHeaders = [
            'Host' => 'wx.xwbank.com',
            'Channelid' => 'XWPTBTC',
            'Mobile' => '',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
            'Sessiontokenkey' => '',
            'Openid' => $openid,
            'Accept' => '*/*',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Referer' => 'https://wx.xwbank.com/xwbank/partner/index.html?channelId=XWPTBTC',
            'Accept-Language' => 'en-us,en',
            'Cookie' => $cookie,
        ];
        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $sendHeaders, [], 'postjson');
    }

    function queryUserStatus(string $openid, string $cookie)
    {
        $url = 'https://wx.xwbank.com/api/ActivityMgm/queryUserStatus';
        $data = ['operateType' => 'GM'];
        $sendHeaders = [
            'Host' => 'wx.xwbank.com',
            'Channelid' => 'XWPTBTC',
            'Mobile' => '',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
            'Sessiontokenkey' => '',
            'Openid' => $openid,
            'Accept' => '*/*',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Referer' => 'https://wx.xwbank.com/xwbank/partner/index.html?channelId=XWPTBTC',
            'Accept-Language' => 'en-us,en',
            'Cookie' => $cookie,
        ];
        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $sendHeaders, [], 'postjson');
    }

}
