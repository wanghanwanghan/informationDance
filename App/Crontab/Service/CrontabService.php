<?php

namespace App\Crontab\Service;

use App\Crontab\CrontabList\DeleteTimeoutOrder;
use App\Crontab\CrontabList\RunSupervisor;
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
        $this->runSupervisor();

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

    //风险监控
    private function runSupervisor()
    {
        return Crontab::getInstance()->addTask(RunSupervisor::class);
    }
}
