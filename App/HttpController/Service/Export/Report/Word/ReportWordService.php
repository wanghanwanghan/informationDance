<?php

namespace App\HttpController\Service\Export\Report\Word;

use App\HttpController\Models\Api\ReportInfo;
use App\HttpController\Service\Report\ReportService;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\CreateEasyReportTask;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class ReportWordService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($code, $paging, $result, $msg): array
    {
        return $this->createReturn($code, $paging, $result, $msg);
    }

    private function createReportNum(): string
    {
        return Carbon::now()->format('YmdHis') . '_' . control::randNum(8);
    }

    //生成一个简版报告
    function createEasy(array $arr): array
    {
        $reportNum = $this->createReportNum();
        $entName = $arr['entName'];
        $appId = $arr['appId'];
        $email = $arr['email'];
        $type = 'xd';// 每日信动专用

        ReportInfo::create()->data([
            'phone' => $appId,
            'email' => $email,
            'entName' => $entName,
            'filename' => $reportNum,
            'ext' => 'docx',
            'type' => ReportService::REPORT_TYPE_30,
            'status' => 3,//1是异常，2是完成，3是生成中
            'errInfo' => '',
            'belong' => $type,
            'dataKey' => '',
        ])->save();

        //扔到task里
        TaskService::getInstance()->create(new CreateEasyReportTask($entName, $reportNum, $appId, $type));

        return $this->checkResp(200, null, null, '报告生成中');
    }


}
