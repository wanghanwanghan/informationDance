<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\JinCaiRwh;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class GetInvDataJinCai extends AbstractCronTask
{
    public $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        // 每天凌晨扫一遍任务号
        // created_at 前一天 再往前

        $created_at = Carbon::now()->subDays(1)->endOfDay()->timestamp;

        $id = 1;

        while (true) {

            $info = JinCaiRwh::create()
                ->where('created_at', $created_at, '<=')
                ->where('isComplete', 0)// 未完成
                ->page($id, 100)
                ->get()->toArray();

            if (empty($info)) break;


            $id++;

        }


    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
