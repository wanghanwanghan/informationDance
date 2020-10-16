<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Pay\wx\wxPayService;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\WeChat;
use EasySwoole\RedisPool\Redis;

class NotifyController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //微信通知
    function wxNotify()
    {
        //{
        //    "appid":"wxc35b4c5377218f34",
        //    "bank_type":"OTHERS",
        //    "cash_fee":"5",
        //    "fee_type":"CNY",
        //    "is_subscribe":"N",
        //    "mch_id":"1602770951",
        //    "nonce_str":"edh2xu17wPrS8DpZgB3N4KatLscnAGXv",
        //    "openid":"ovEPn5cQoswvSkqDuAp8yf2F5uio",
        //    "out_trade_no":"2020101310291732000",
        //    "result_code":"SUCCESS",
        //    "return_code":"SUCCESS",
        //    "sign":"098E8FE97A8069307ADF4AE5F0EFDF4B",
        //    "time_end":"20201013103042",
        //    "total_fee":"5",
        //    "trade_type":"JSAPI",
        //    "transaction_id":"4200000731202010136366605001"
        //}

        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try
        {
            $data = $pay->weChat((new wxPayService())->getConf())->verify($content);

            $data = jsonDecode(jsonEncode($data));

        }catch (\Throwable $e)
        {
            $data = [];
        }

        //出错就不执行了
        if (empty($data)) return true;

        //拿订单信息
        $orderInfo=PurchaseInfo::create()->where('orderId',$data['out_trade_no'])->get();

        if (empty($orderInfo)) return true;

        //检查回调中的支付状态
        if (strtoupper($data['result_code']) === 'SUCCESS')
        {
            //支付成功
            $status='已支付';

            $walletInfo = Wallet::create()->where('phone',$orderInfo->phone)->get();

            $PurchaseList = PurchaseList::create()->get($orderInfo->purchaseType);

            $payMoney = $walletInfo->payMoney + $PurchaseList->money;

            //给用户加余额
            $walletInfo->update(['payMoney'=>$payMoney]);

        }else
        {
            //支付失败
            $status='异常';
        }

        //更改订单状态
        $orderInfo->update(['status'=>$status]);

        return $this->response()->write(WeChat::success());
    }









}