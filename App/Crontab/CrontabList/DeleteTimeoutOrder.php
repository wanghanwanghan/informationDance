<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\PurchaseInfo;
use App\HttpController\Service\Common\CommonService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;

class DeleteTimeoutOrder extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '30 4 * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex): bool
    {
        //$workerIndex是task进程编号
        //taskId是进程周期内第几个task任务
        //可以用task，也可以用process

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) {
            CommonService::getInstance()->log4PHP(__CLASS__ . '不开始');
            return true;
        }

        try {
            $now = Carbon::now()->subDay()->timestamp;
            PurchaseInfo::create()->destroy(function (QueryBuilder $builder) use ($now) {
                $builder->where('orderStatus', '待支付')->where('created_at', $now, '<');
            });
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getMessage());
        }

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $this->crontabBase->removeOverlappingKey(self::getTaskName());
        CommonService::getInstance()->log4PHP($throwable->getMessage());
    }


}
