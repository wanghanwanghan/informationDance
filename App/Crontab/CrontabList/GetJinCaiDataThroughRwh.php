<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\JinCaiRwh;
use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessList\GetInvDataJinCai;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\RedisPool\Redis;

class GetJinCaiDataThroughRwh extends AbstractCronTask
{
    public $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '*/30 * * * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $redis = Redis::defer('redis');
        $redis->select(15);

        // 判断队列长度，太长就先不加了
        $llen = $redis->lLen(GetInvDataJinCai::QueueKey);
        if ($llen > 500) {
            return;
        }

        $page = 1;

        while (true) {
            // 到rwh表里的数据，肯定是已经执行采集超过1小时的
            $rwh_list = JinCaiRwh::create()
                ->where('isComplete', 0)
                ->page($page, 100)->all();
            if (empty($rwh_list)) break;
            foreach ($rwh_list as $one_rwh) {
                $one_rwh->update(['isComplete' => 2]);// 任务号表的这条数据状态改为执行中
                $one_rwh = obj2Arr($one_rwh);
                $one_rwh['isComplete'] = 2;
                $redis->lPush(GetInvDataJinCai::QueueKey, jsonEncode($one_rwh, false));
            }
            $page++;
        }

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content, 'error', 'GetJinCaiDataThroughRwh.log');
    }

}
