<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Models\Api\PurchaseList;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\Pay\ali\aliPayService;
use App\HttpController\Service\Pay\wx\wxPayService;
use EasySwoole\Pay\AliPay\AliPay;
use EasySwoole\Pay\AliPay\RequestBean\NotifyRequest;
use EasySwoole\Pay\Pay;
use EasySwoole\Pay\WeChat\WeChat;

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

    //给用户加余额的时候多加一点
    private function exprAddMoney($money): float
    {
        return $money + round($money * 0.3, 2);
    }

    //微信小程序通知 信动
    function wxNotify()
    {
        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try {
            $data = $pay->weChat((new wxPayService())->getConf('miniapp'))->verify($content);
            $data = obj2Arr($data);
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

            $payMoney = $walletInfo->getAttr('money') + $this->exprAddMoney($PurchaseList->getAttr('money'));

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

    //微信小程序通知 伟衡
    function wxNotify_wh()
    {
        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try {
            $data = $pay->weChat((new wxPayService())->setPayConfType('wh')->getConf('miniapp'))->verify($content);
            $data = obj2Arr($data);
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

            $walletInfo = Wallet::create()->where('phone', $orderInfo->getAttr('phone'))->get();

            $PurchaseList = PurchaseList::create()->get($orderInfo->getAttr('purchaseType'));

            $payMoney = $walletInfo->getAttr('money') + $this->exprAddMoney($PurchaseList->getAttr('money'));

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

    //微信扫码通知 信动
    function wxNotifyScan()
    {
        $pay = new Pay();

        $content = $this->request()->getBody()->__toString();

        try {
            $data = $pay->weChat((new wxPayService())->getConf('scan'))->verify($content);
            $data = obj2Arr($data);
        } catch (\Throwable $e) {
            $data = [];
        }

        //出错就不执行了
        if (empty($data)) return true;

        //{
        //    "appid":"wxc35b4c5377218f34",
        //    "bank_type":"OTHERS",
        //    "cash_fee":"1",
        //    "fee_type":"CNY",
        //    "is_subscribe":"N",
        //    "mch_id":"1602770951",
        //    "nonce_str":"d7sg1YQR23apAScF6PqN9LyT0utxEVIl",
        //    "openid":"ovEPn5aZdrEPlJLGiiTCrrHko9iw",
        //    "out_trade_no":"ee359fac50c3ef06",
        //    "result_code":"SUCCESS",
        //    "return_code":"SUCCESS",
        //    "sign":"5A966200F484C7AE7A309AE3343740D2",
        //    "time_end":"20210222125229",
        //    "total_fee":"1",
        //    "trade_type":"NATIVE",
        //    "transaction_id":"4200000891202102225144645624"
        //}

        //拿订单信息
        $orderInfo = PurchaseInfo::create()->where('orderId', $data['out_trade_no'])->get();

        if (empty($orderInfo)) return true;

        //检查回调中的支付状态
        if (strtoupper($data['result_code']) === 'SUCCESS') {

            //支付成功
            $status = '已支付';

            $walletInfo = Wallet::create()->where('phone', $orderInfo->getAttr('phone'))->get();

            $PurchaseList = PurchaseList::create()->get($orderInfo->getAttr('purchaseType'));

            $payMoney = $walletInfo->getAttr('money') + $this->exprAddMoney($PurchaseList->getAttr('money'));

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

    //支付宝扫码通知 信动
    function aliNotifyScan()
    {
        $aliConfig = (new aliPayService())->getConfig();

        $pay = new Pay();

        $param = $this->request()->getBody()->__toString();

        parse_str($param, $data);

        //需要忽略sign_type组装
        unset($data['sign_type']);

        $order = new NotifyRequest($data, true);
        $aliPay = $pay->aliPay($aliConfig);
        $result = $aliPay->verify($order);

        $out_trade_no = $data['out_trade_no'];

        //拿订单信息
        $orderInfo = PurchaseInfo::create()->where('orderId', $out_trade_no)->get();

        if (true === $result) {
            $walletInfo = Wallet::create()->where('phone', $orderInfo->phone)->get();
            $PurchaseList = PurchaseList::create()->get($orderInfo->purchaseType);
            $payMoney = $walletInfo->money + $this->exprAddMoney($PurchaseList->money);
            //给用户加余额
            $walletInfo->update(['money' => $payMoney]);
            //更改订单状态
            $orderInfo->update(['orderStatus' => '已支付']);
            return $this->response()->write(AliPay::success());
        } else {
            //更改订单状态
            $orderInfo->update(['orderStatus' => '异常']);
            CommonService::getInstance()->log4PHP($data, 'Pay', __FUNCTION__ . '.log');
            return $this->response()->write(AliPay::fail());
        }
    }

    //授权认证通知
    function zwAuthNotify()
    {
        $entName = $this->getRequestData('name', '');
        $taxNo = $this->getRequestData('taxNumber', '');
        $state = $this->getRequestData('state', '');
        $message = $this->getRequestData('massge', '');
        $orderNo = $this->getRequestData('orderNo', '');

        $phone = substr($orderNo, 0, 11);
        $time = substr($orderNo, -10);

        try {
            $check = AuthBook::create()->where(['phone' => $phone, 'remark' => $orderNo])->get();
            if (!empty($check)) {
                $check->update(['status' => 3, 'remark' => $state . $message . __FUNCTION__]);
            }
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        }

        return $this->response()->write(jsonEncode(['code' => 0, 'msg' => '成功', 'data' => null], false));
    }

    //获取数据通知
    function zwDataNotify()
    {
        $entName = $this->getRequestData('name', '');
        $taxNo = $this->getRequestData('taxNumber', '');
        $state = $this->getRequestData('state', '');
        $type = $this->getRequestData('type', '');
        $message = $this->getRequestData('massge', '');
        $orderNo = $this->getRequestData('orderNo', '');

        $phone = substr($orderNo, 0, 11);
        $time = substr($orderNo, -10);

        try {
            $check = AuthBook::create()->where(['phone' => $phone, 'remark' => $orderNo])->get();
            if (!empty($check)) {
                $check->update(['status' => 3, 'remark' => $state . $message . __FUNCTION__]);
            }
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        }

        return $this->response()->write(jsonEncode(['code' => 0, 'msg' => '成功', 'data' => null], false));
    }


}