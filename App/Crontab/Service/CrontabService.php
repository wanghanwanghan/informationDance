<?php

namespace App\Crontab\Service;

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

        return true;
    }

    //测试定时任务
    private function test()
    {
        return Crontab::getInstance()->addTask(TestCrontab::class);
    }
}
