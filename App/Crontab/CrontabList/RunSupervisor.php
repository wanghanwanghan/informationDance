<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\SupervisorEntNameInfo;
use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class RunSupervisor extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * *';
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

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) return true;

        //取出本次要监控的企业列表
        $target = SupervisorPhoneEntName::create()
            ->where('status', 1)->where('expireTime', time(), '>')
            ->get()->toArray();

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        CommonService::getInstance()->log4PHP('这里运行了1');

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP('这里运行了2');
        $this->crontabBase->removeOverlappingKey(self::getTaskName());
        CommonService::getInstance()->log4PHP('这里运行了3');
    }


}
