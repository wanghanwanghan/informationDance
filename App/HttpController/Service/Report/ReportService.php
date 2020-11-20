<?php

namespace App\HttpController\Service\Report;

use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateEasyReportTask;
use App\Task\TaskList\CreateVeryEasyReportTask;
use EasySwoole\Component\Singleton;

class ReportService extends ServiceBase
{
    use Singleton;

    //生成极简报告
    function createVeryEasy($entName, $reportNum, $phone, $type)
    {
        try {
            ReportInfo::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'filename' => $reportNum,
                'type' => 10,
                'status' => 3,
                'errInfo' => '',
            ])->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //扔到task里
        TaskService::getInstance()->create(new CreateVeryEasyReportTask($entName, $reportNum, $phone, $type));

        return $reportNum;
    }

    //生成简版报告
    function createEasy($entName, $reportNum, $phone, $type)
    {
        try {
            ReportInfo::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'filename' => $reportNum,
                'type' => 30,
                'status' => 3,
                'errInfo' => '',
            ])->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $phone, $type));

        return $reportNum;
    }

    //生成深度报告
    function createDeep($entName, $reportNum, $phone, $type)
    {
        try {
            ReportInfo::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'filename' => $reportNum,
                'type' => 50,
                'status' => 3,
                'errInfo' => '',
                'belong' => $type,
            ])->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //不扔task了，等后台审核后再扔task
        //TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $phone, $type));

        return $reportNum;
    }

}
