<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Common\CommonService;
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
        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try {

            $data = $pay->weChat((new wxPayService())->getConf())->verify($content);

            $data = jsonDecode(jsonEncode($data));

        } catch (\Throwable $e) {
            $data = [];
        }

        //出错就不执行了
        if (empty($data)) return true;

        //拿订单信息
        $orderInfo = PurchaseInfo::create()->where('orderId', $data['out_trade_no'])->get();

        if (empty($orderInfo)) return true;

        //检查回调中的支付状态
        if (strtoupper($data['result_code']) === 'SUCCESS') {

            //支付成功
            $status = '已支付';

            $walletInfo = Wallet::create()->where('phone', $orderInfo->phone)->get();

            $PurchaseList = PurchaseList::create()->get($orderInfo->purchaseType);

            $payMoney = $walletInfo->money + $PurchaseList->money;

            //给用户加余额
            $walletInfo->update(['money' => $payMoney]);

        } else {
            //支付失败
            $status = '异常';
            CommonService::getInstance()->log4PHP($data, 'Pay', __FUNCTION__ . '.log');
        }

        //更改订单状态
        $orderInfo->update(['orderStatus' => $status]);

        return $this->response()->write(WeChat::success());
    }


}