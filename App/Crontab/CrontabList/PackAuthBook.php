<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class PackAuthBook extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每月18号的23点执行一次
        //return '0 23 18 * *';
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP(Carbon::now()->format('Ymd'), 'PackAuthBookCrontabRunAt', 'ant.log');

        //准备获取授权书的企业列表
        $list = AntAuthList::create()->where([
            'status' => MaYiService::STATUS_1,//pdf生成完毕，等待发送给大象
        ])->all();
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'PackAuthBookCrontabException', 'ant.log');
    }


}
