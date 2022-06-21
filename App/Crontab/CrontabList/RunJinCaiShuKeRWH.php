<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class RunJinCaiShuKeRWH extends AbstractCronTask
{
    public $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每分钟执行一次
        return '5 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        (new JinCaiShuKeService())->setCheckRespFlag(true)->getRwhData();
    }
    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'info', 'CrontabList_RunDianZiQianGetPdf');
    }
}