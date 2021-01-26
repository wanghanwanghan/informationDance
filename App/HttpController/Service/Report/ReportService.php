<?php

namespace App\HttpController\Service\Report;

use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateEasyReportCustomizedTask;
use App\Task\TaskList\CreateEasyReportTask;
use App\Task\TaskList\CreateVeryEasyReportTask;
use EasySwoole\Component\Singleton;

class ReportService extends ServiceBase
{
    use Singleton;

    const REPORT_TYPE_10 = 10;//10是极简报告
    const REPORT_TYPE_30 = 30;//30是简版报告
    const REPORT_TYPE_31 = 31;//31是简版报告定制版pdf版
    const REPORT_TYPE_50 = 50;//50是深度报告

    //生成极简报告
    function createVeryEasy($entName, $reportNum, $phone, $type)
    {
        try {
            ReportInfo::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'filename' => $reportNum,
                'type' => ReportService::REPORT_TYPE_10,
                'status' => 3,//1是异常，2是完成，3是生成中
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
                'type' => ReportService::REPORT_TYPE_30,
                'status' => 3,//1是异常，2是完成，3是生成中
                'errInfo' => '',
            ])->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $phone, $type));

        return $reportNum;
    }

    //生成简版报告 pdf
    function createEasyPdf($entName, $reportNum, $phone, $type, $dataKey = '')
    {
        try {
            ReportInfo::create()->data([
                'phone' => $phone,
                'entName' => $entName,
                'filename' => $reportNum,
                'type' => ReportService::REPORT_TYPE_31,
                'status' => 3,//1是异常，2是完成，3是生成中
                'errInfo' => '',
            ])->save();

        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        //扔到task里
        TaskService::getInstance()
            ->create(new CreateEasyReportCustomizedTask($entName, $reportNum, $phone, $type, $dataKey));

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
                'type' => ReportService::REPORT_TYPE_50,
                'status' => 3,//1是异常，2是完成，3是生成中
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
