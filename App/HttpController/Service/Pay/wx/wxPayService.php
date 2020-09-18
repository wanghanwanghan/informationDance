<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\Pay\PayBase;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\Config as wxConf;
use EasySwoole\Pay\WeChat\RequestBean\MiniProgram;

class wxPayService extends PayBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function getConf(): wxConf
    {
        $conf = new wxConf();

        $conf->setAppId(\Yaconf::get('wx.appId'));
        $conf->setMiniAppId(\Yaconf::get('wx.miniAppId'));
        $conf->setMchId(\Yaconf::get('wx.mchId'));
        $conf->setKey(\Yaconf::get('wx.miniPayKey'));
        $conf->setNotifyUrl(\Yaconf::get('wx.notifyUrl'));
        $conf->setApiClientCert(implode(PHP_EOL, \Yaconf::get('wx.cert')));
        $conf->setApiClientKey(implode(PHP_EOL, \Yaconf::get('wx.key')));

        return $conf;
    }

    private function getOpenId($code): array
    {
        $url = \Yaconf::get('wx.getOpenIdUrl');

        $data = [
            'appid' => \Yaconf::get('wx.miniAppId'),
            'secret' => \Yaconf::get('wx.openIdKey'),
            'js_code' => $code,//这是从wx.login中拿的
            'grant_type' => 'authorization_code',
        ];

        return (new CoHttpClient())->needJsonDecode(true)->send($url, $data, [], [], 'get');
    }

    //返回一个小程序支付resp对象
    function miniAppPay(string $jsCode, string $orderId, string $money, string $body, string $ipForCli)
    {
        $bean = new MiniProgram();

        //用户的openid
        $bean->setOpenid(end($this->getOpenId($jsCode)));

        //订单号
        $bean->setOutTradeNo($orderId);

        //订单body
        $bean->setBody($body);

        //金额
        $bean->setTotalFee($money * 100);

        //终端ip
        $bean->setSpbillCreateIp($ipForCli);

        $pay = new Pay();

        $params = $pay->weChat($this->getConf())->miniProgram($bean);

        return $params;
    }





}
