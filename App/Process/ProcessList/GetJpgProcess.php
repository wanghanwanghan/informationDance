<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\Process\ProcessBase;
use Swoole\Process;

class GetJpgProcess extends ProcessBase
{
    public $openid = 'or9pL5BaXeNTx4ikA1FglWKsFOqU';
    public $jsluid_s = '';
    public $sessionId = '';
    public $token = '';
    public $mobile = '13269706193';
    public $partnerUnionId = 'otb0Z1qDT9H6iZJa0lJVfKombbUs';

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        sleep(86400);
        return;



        $smsCodeSend = $this->smsCodeSend();

        CommonService::getInstance()->log4PHP([$smsCodeSend, '发送登录验证码'], 'info', 'wgetWBHjpg.log');

        sleep(3);

        $s_time = time() - 20;
        $e_time = $s_time + 180;

        while (true) {
            // 循环120秒等邮箱接收
            $sql = <<<EOF
SELECT
	* 
FROM
	store_vcode 
WHERE
	created_at BETWEEN {$s_time} 
	AND {$e_time} 
	AND isUse = 0 
	AND type = 0 
ORDER BY
	created_at DESC 
	LIMIT 1;
EOF;
            $res = sqlRaw($sql);
            if (!empty($res)) {
                $smsCodeVerify = $this->smsCodeVerify($res[0]['vcode']);
                CommonService::getInstance()->log4PHP([$smsCodeSend, '拿到vcode后登录'], 'info', 'wgetWBHjpg.log');
                $this->sessionId = $smsCodeVerify['result']['sessionId'];
                $this->token = $smsCodeVerify['result']['token'];
                break;
            } else {
                sleep(2);
                if ($e_time - time() <= 0) {
                    break;
                }
            }
        }

        $check = false;

        $arr = $this->saveJpg();

        if (!empty(
            $arr['result']['data']) &&
            substr($arr['result']['data']['qrcode_base64'], 0, 15) === 'data:image/jpeg'
        ) {
            $check = true;
            $stream = str_replace('data:image/jpeg;base64,', '', $arr['result']['data']['qrcode_base64']);
            file_put_contents(IMAGE_PATH . 'wbh.jpg', base64_decode($stream));
        }

        if (!$check) {
            CommonService::getInstance()->log4PHP([$arr, 'check居然是false'], 'info', 'wgetWBHjpg.log');
        }

        try {
            $t = random_int(28800, 43200);// 8 - 12 小时
        } catch (\Throwable $e) {
            $t = 43200;
        }

        sleep($t);

    }

    function queryUserStatus(string $openid, string $cookie)
    {
        $url = 'https://wx.xwbank.com/api/ActivityMgm/queryUserStatus';
        $data = [
            'operateType' => 'GM'
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

    function smsCodeSend()
    {
        $url = 'https://wx.xwbank.com/api2.0/zyVerify/smsCodeSend';
        $data = [
            'mobileNo' => $this->mobile,
            'tranType' => 'LOGIN'
        ];
        $sendHeaders = [
            'Host' => 'wx.xwbank.com',
            'Channelid' => 'XWPTBTC',
            'Mobile' => '',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
            'Sessiontokenkey' => '',
            'Openid' => $this->openid,
            'Accept' => '*/*',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Referer' => 'https://wx.xwbank.com/xwbank/partner/index.html',
            'Accept-Language' => 'en-us,en',
        ];
        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $sendHeaders, [], 'postjson');
    }

    function smsCodeVerify(string $smsCode)
    {
        $url = 'https://wx.xwbank.com/api2.0/zyVerify/smsCodeVerify';
        $data = [
            'mobileNo' => $this->mobile,
            'smsCode' => $smsCode,
            'tranType' => 'LOGIN',
            'error' => true,
            'lon' => 0,
            'lat' => 0,
            'partnerUnionId' => $this->partnerUnionId,
            'partnerOpenId' => $this->openid,
            'codeUrl' => 'https://wx.xwbank.com/xwbank/partner/index.html',
        ];
        $sendHeaders = [
            'Host' => 'wx.xwbank.com',
            'Channelid' => 'XWPTBTC',
            'Mobile' => '',
            'Content-Type' => 'application/json',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/81.0.4044.138 Safari/537.36 MicroMessenger/7.0.9.501 NetType/WIFI MiniProgramEnv/Windows WindowsWechat',
            'Sessiontokenkey' => '',
            'Openid' => $this->openid,
            'Accept' => '*/*',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Referer' => 'https://wx.xwbank.com/xwbank/partner/index.html',
            'Accept-Language' => 'en-us,en',
        ];
        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $sendHeaders, [], 'postjson');
    }

    function saveJpg()
    {
        $url = 'https://wx.xwbank.com/api/ActivityMgm/partnerInviteQCode';
        $data = [
            'actId' => '1001049',
            'openId' => '1140594606763495424',
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
            'Openid' => $this->openid,
            'Accept' => '*/*',
            'Sec-Fetch-Site' => 'same-origin',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Dest' => 'empty',
            'Referer' => 'https://wx.xwbank.com/xwbank/partner/index.html?channelId=XWPTBTC',
            'Accept-Language' => 'en-us,en',
            'Cookie' => $this->getCookie(),
        ];
        return (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $sendHeaders, [], 'postjson');
    }

    protected function getCookie(): string
    {
        return sprintf('__jsluid_s=%s; sessionId=%s; token=%s;', $this->jsluid_s, $this->sessionId, $this->token);
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }


}
