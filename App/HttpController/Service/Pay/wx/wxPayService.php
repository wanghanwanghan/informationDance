<?php

namespace App\HttpController\Service\Pay\wx;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\Pay\PayBase;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\Config as wxConf;
use EasySwoole\Pay\WeChat\RequestBean\MiniProgram;
use EasySwoole\Pay\WeChat\RequestBean\Scan;
use wanghanwanghan\someUtils\control;

class wxPayService extends PayBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    //信动 = xd 伟衡 = wh
    public $payConfType = 'xd';

    function setPayConfType($type)
    {
        $this->payConfType = $type;
        return $this;
    }

    function getConf($type = 'miniapp'): wxConf
    {
        $conf = new wxConf();

        switch ($this->payConfType) {
            case 'xd':
                $conf->setAppId(CreateConf::getInstance()->getConf('wx.miniAppId'));
                $conf->setMiniAppId(CreateConf::getInstance()->getConf('wx.miniAppId'));
                $conf->setMchId(CreateConf::getInstance()->getConf('wx.mchId'));
                $conf->setKey(CreateConf::getInstance()->getConf('wx.miniPayKey'));
                switch ($type) {
                    case 'miniapp':
                        $conf->setNotifyUrl(CreateConf::getInstance()->getConf('wx.notifyUrl'));
                        break;
                    case 'scan':
                        $conf->setNotifyUrl(CreateConf::getInstance()->getConf('wx.notifyUrlWxScan'));
                        break;
                }
                $conf->setApiClientCert(implode(PHP_EOL, CreateConf::getInstance()->getConf('wx.cert')));
                $conf->setApiClientKey(implode(PHP_EOL, CreateConf::getInstance()->getConf('wx.key')));
                break;
            case 'wh':
                $conf->setAppId(CreateConf::getInstance()->getConf('wx.miniAppId'));
                $conf->setMiniAppId(CreateConf::getInstance()->getConf('wx.miniAppIdWh'));
                $conf->setMchId(CreateConf::getInstance()->getConf('wx.mchId'));
                $conf->setKey(CreateConf::getInstance()->getConf('wx.miniPayKeyWh'));
                $conf->setNotifyUrl(CreateConf::getInstance()->getConf('wx.notifyUrlWh'));
                $conf->setApiClientCert(implode(PHP_EOL, CreateConf::getInstance()->getConf('wx.cert')));
                $conf->setApiClientKey(implode(PHP_EOL, CreateConf::getInstance()->getConf('wx.key')));
                break;
        }

        return $conf;
    }

    private function getOpenId($code): array
    {
        $url = CreateConf::getInstance()->getConf('wx.getOpenIdUrl');

        switch ($this->payConfType) {
            case 'xd':
                $data = [
                    'appid' => CreateConf::getInstance()->getConf('wx.miniAppId'),
                    'secret' => CreateConf::getInstance()->getConf('wx.openIdKey'),
                    'js_code' => $code,//这是从wx.login中拿的
                    'grant_type' => 'authorization_code',
                ];
                break;
            case 'wh':
                $data = [
                    'appid' => CreateConf::getInstance()->getConf('wx.miniAppIdWh'),
                    'secret' => CreateConf::getInstance()->getConf('wx.openIdKeyWh'),
                    'js_code' => $code,//这是从wx.login中拿的
                    'grant_type' => 'authorization_code',
                ];
                break;
        }

        $url .= '?' . http_build_query($data);

        return (new CoHttpClient())->needJsonDecode(true)->send($url, $data, [], [], 'get');
    }

    //返回一个小程序支付resp对象
    function miniAppPay(string $jsCode, string $orderId, string $money, string $body, string $ipForCli = '')
    {
        $bean = new MiniProgram();

        //用户的openid
        $openId = $this->getOpenId($jsCode);
        $bean->setOpenid(end($openId));

        //订单号
        $bean->setOutTradeNo($orderId);

        //订单body
        $bean->setBody($body);

        //金额
        $bean->setTotalFee($money * 100);

        //终端ip，据说高版本不用传了
        if (!empty($ipForCli)) $bean->setSpbillCreateIp($ipForCli);

        $pay = new Pay();

        $params = $pay->weChat($this->getConf('miniapp'))->miniProgram($bean);

        return $params;
    }

    //返回扫码url
    function scan($orderId, $payMoney, $subject, $ipForCli = '')
    {
        $outTradeNo = $orderId;

        $bean = new Scan();

        $bean->setOutTradeNo($outTradeNo);

        // $bean->setProductId('123456789');

        $bean->setBody($subject);

        $fee = $payMoney * 100;

        $bean->setTotalFee((string)$fee);

        if (!empty($ipForCli)) $bean->setSpbillCreateIp($ipForCli);

        $pay = new Pay();

        $data = $pay->weChat($this->getConf('scan'))->scan($bean);

        return $data->getCodeUrl();
    }

}
