<?php

namespace App\Crontab\Service;

use App\Crontab\CrontabList\DeleteTimeoutOrder;
use App\Crontab\CrontabList\TestCrontab;
use EasySwoole\Component\Singleton;
use EasySwoole\EasySwoole\Crontab\Crontab;

class CrontabService
{
    use Singleton;

    //只能在mainServerCreate中调用
    function create()
    {
        $this->test();
        $this->deleteTimeoutOrder();

        return true;
    }

    //测试定时任务
    private function test()
    {
        return Crontab::getInstance()->addTask(TestCrontab::class);
    }

    //删除待支付订单
    private function deleteTimeoutOrder()
    {
        return Crontab::getInstance()->addTask(DeleteTimeoutOrder::class);
    }
}
