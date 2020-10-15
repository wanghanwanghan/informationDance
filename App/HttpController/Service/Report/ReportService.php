<?php

namespace App\HttpController\Service\Report;

use App\HttpController\Models\Api\ReportInfo;
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
        try
        {
            ReportInfo::create()->data([
                'phone'=>$phone,
                'entName'=>$entName,
                'filename'=>$reportNum,
                'type'=>30,
                'status'=>3,
            ])->save();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,__FUNCTION__);
        }

        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $phone));

        return $reportNum;
    }


}
