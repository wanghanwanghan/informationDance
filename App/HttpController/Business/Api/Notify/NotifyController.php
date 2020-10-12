<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
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

        $redis=Redis::defer('redis');

        $redis->select(13);

        $content = $this->request()->getBody()->__toString();

        $redis->set('wxNotifyContent',jsonEncode($content));

        try
        {
            $data = $pay->weChat((new wxPayService())->getConf())->verify($content);

        }catch (\Throwable $e)
        {
            $redis->set('wxNotifyErr',jsonEncode($e->getMessage()));
        }

        $redis->set('wxNotifyData',jsonEncode($data));

        $this->response()->write(WeChat::success());

        return true;
    }










}