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
        //01增值税专用发票
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票
        //10增值税普通发票电子
        //11增值税普通发票卷式
        //14通行费电子票
        //15二手车销售统一发票

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
