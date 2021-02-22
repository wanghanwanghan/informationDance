<?php

namespace App\HttpController\Service\Pay\ali;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\Pay\PayBase;
use EasySwoole\Pay\AliPay\Config;
use EasySwoole\Pay\AliPay\GateWay;
use EasySwoole\Pay\AliPay\RequestBean\Scan;
use EasySwoole\Pay\Pay;

class aliPayService extends PayBase
{
    //信动 = xd 伟衡 = wh
    public $payConfType = 'xd';

    function setPayConfType($type)
    {
        $this->payConfType = $type;
        return $this;
    }

    function getConfig()
    {
        $aliConfig = new Config();
        $aliConfig->setGateWay(GateWay::NORMAL);
        $aliConfig->setAppId(CreateConf::getInstance()->getConf('ali.appId'));
        $aliConfig->setPublicKey(CreateConf::getInstance()->getConf('ali.aliPubKey'));
        $aliConfig->setPrivateKey(CreateConf::getInstance()->getConf('ali.appSecKey'));
        $aliConfig->setNotifyUrl(CreateConf::getInstance()->getConf('ali.notifyUrlAliScan'));

        return $aliConfig;
    }

    function scan($orderId, $payMoney, $subject)
    {
        $pay = new Pay();

        $order = new Scan();
        $order->setSubject($subject);
        $order->setTotalAmount((int)$payMoney);
        $order->setOutTradeNo($orderId);

        try {
            $aliPay = $pay->aliPay($this->getConfig());
            $data = $aliPay->scan($order)->toArray();
            $response = $aliPay->preQuest($data);
            $response = $response['qr_code'];
        } catch (\Throwable $e) {
            $response = '';
        }

        return $response;
    }


}
