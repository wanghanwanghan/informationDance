<?php

namespace App\HttpController\Service\Report;

use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateEasyReportTask;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class ReportService extends ServiceBase
{
    use Singleton;

    //生成简版报告
    function createEasy($entName,$reportNum)
    {
        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum));

        return $reportNum;
    }


}
