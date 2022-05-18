<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\Report\ReportService;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateDeepReportTask;
use App\Task\TaskList\CreateEasyReportCustomizedTask;
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
        return '*/5 * * * *';
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
        $info = ReportInfo::create()
            ->where('type', [ReportService::REPORT_TYPE_50, ReportService::REPORT_TYPE_51], 'in')
            ->where('status', 3)
            ->all();

        $info = obj2Arr($info);

        $check = $this->crontabBase->withoutOverlapping(self::getTaskName());

        if ($check) {
            foreach ($info as $one) {
                //看看这公司的授权书有没有通过
                $entName = $one['entName'];
                //后台审核通过后才能生成
                $authInfo = AuthBook::create()->where('entName', $entName)->where('status', 3)->get();
                //没通过就继续下一条
                if (empty($authInfo)) continue;
                if ($one['type'] == ReportService::REPORT_TYPE_50) {
                    //word
                    TaskService::getInstance()->create(new CreateDeepReportTask(
                        $entName,
                        $one['code'],
                        $one['filename'],
                        $one['phone'],
                        $one['belong']
                    ), 'sync');
                } elseif ($one['type'] == ReportService::REPORT_TYPE_51) {
                    //pdf
                    TaskService::getInstance()
                        ->create(new CreateEasyReportCustomizedTask(
                            $entName,
                            $one['filename'],
                            $one['phone'],
                            $one['belong'],
                            $one['dataKey'],
                            ReportService::REPORT_TYPE_51
                        ), 'sync');
                }
            }
            $this->crontabBase->removeOverlappingKey(self::getTaskName());
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getMessage());
    }
}
