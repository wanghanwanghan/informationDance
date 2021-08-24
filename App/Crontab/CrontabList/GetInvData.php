<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\RedisPool\Redis;

class GetInvData extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '*/30 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $redis = Redis::defer('redis');
        $redis->select(15);

        for ($i = 1; $i <= 10000; $i++) {
            $limit = 1000;
            $offset = ($i - 1) * $limit;
            $list = AntAuthList::create()
                ->where('status', MaYiService::STATUS_3)
                ->limit($offset, $limit)->all();
            if (empty($limit)) {
                break;
            }
            foreach ($list as $one) {
                $id = $one->getAttr('id');
                $suffix = $id % 16;
                //放到redis队列
                $key = 'readyToGetInvData_' . $suffix;
                $redis->lPush($key, jsonEncode($one, false));
            }
        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'GetInvDataCrontabException', 'ant.log');
    }

}
