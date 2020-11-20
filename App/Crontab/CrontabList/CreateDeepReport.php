<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\Common\CommonService;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateDeepReportTask;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class CreateDeepReport extends AbstractCronTask
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

        //取出未生产的深度报告
        $info = ReportInfo::create()->where('type', 50)->where('status', 3)->all();

        $info = obj2Arr($info);

        $check = $this->crontabBase->withoutOverlapping(self::getTaskName());

        if ($check)
        {
            foreach ($info as $one)
            {
                //看看这公司的授权书有没有通过
                $entName = $one['entName'];

                $authInfo = AuthBook::create()->where('entName', $entName)->where('status', 3)->get();

                //没通过就继续下一条
                if (empty($authInfo)) continue;

                TaskService::getInstance()
                    ->create(new CreateDeepReportTask($entName, $one['reportNum'], $one['phone'], $one['belong']),'sync');
            }

            $this->crontabBase->removeOverlappingKey(self::getTaskName());
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getMessage());
    }


}
