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

        //91110108MA01KPGK0L 北京每日信动科技有限公司
        //91110105690802464Y 北控水务（中国）投资有限公司
        //91450800200451746R 广西贵港北控水务有限公司
        //91510700720874769J 绵阳中科成污水净化有限公司

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
                    ->create(new CreateDeepReportTask($entName,$one['code'],$one['filename'],$one['phone'],$one['belong']),'sync');
            }

            $this->crontabBase->removeOverlappingKey(self::getTaskName());
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getMessage());
    }


}
