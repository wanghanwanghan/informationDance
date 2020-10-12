<?php

namespace App\HttpController\Business\Api\Notify;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Models\Api\User;
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

        $data = $pay->weChat((new wxPayService())->getConf())->verify($content);

        $redis=Redis::defer('redis');
        $redis->select(13);
        $redis->set('wxNotify',jsonEncode($data));

        $this->response()->write(WeChat::success());

        return true;
    }

    function test()
    {
        $res=User::create()->where('phone',123)->get();

        var_export($res);







    }











}