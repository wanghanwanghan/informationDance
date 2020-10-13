<?php

namespace App\HttpController\Service\Report;

use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateEasyReportTask;
use EasySwoole\Component\Singleton;

class ReportService extends ServiceBase
{
    use Singleton;

    //生成简版报告
    function createEasy($entName, $reportNum, $phone)
    {
        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $phone));

        return $reportNum;
    }


}
