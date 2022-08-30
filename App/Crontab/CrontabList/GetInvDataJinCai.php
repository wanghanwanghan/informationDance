<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\JinCaiRwh;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
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
        $created_at = Carbon::now()->subDays(1)->endOfDay()->timestamp;

        $page = 1;

        while (true) {

            $info = JinCaiRwh::create()
                ->where('created_at', $created_at, '<=')
                ->where('isComplete', 0)// 未完成
                ->page($page, 50)
                ->get()->toArray();

            if (empty($info)) break;

            foreach ($info as $one_job) {

                //$job_info = (new JinCaiShuKeService())->obtainResultTraceNo($one_job->rwh);

                //result



            }


            $page++;

        }


    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
