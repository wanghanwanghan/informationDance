<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class TestCrontab extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '*/1 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        //$workerIndex是task进程编号
        //taskId是进程周期内第几个task任务
        //可以用task，也可以用process
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        echo $throwable->getMessage();
    }


}
