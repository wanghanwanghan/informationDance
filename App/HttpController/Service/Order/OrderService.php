<?php

namespace App\HttpController\Service\Order;

use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;

class OrderService extends ServiceBase
{
    use Singleton;

    //创建一个19位唯一订单号
    function createOrderId(int $mch, int $module, int $project): int
    {
        //20181214221024 14位
        $prefix = Carbon::now()->format('YmdHis');

        //第15位 支付服务 $mch=1是微信 $mch=2是支付宝
        ($mch > 9 || $mch < 1) ? $prefix .= 0 : $prefix .= $mch;

        //第16-17位 支付的模块
        $prefix .= str_pad($module, 2, 0, STR_PAD_LEFT);

        //第18-19位 支付的模块下面的小项目
        $prefix .= str_pad($project, 2, 0, STR_PAD_LEFT);

        $orderId = $prefix - 0;

        return $orderId;
    }


}
