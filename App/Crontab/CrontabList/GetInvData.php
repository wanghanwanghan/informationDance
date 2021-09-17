<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\MaYi\MaYiService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\RedisPool\Redis;

class GetInvData extends AbstractCronTask
{
    public $crontabBase;
    public $redisKey = 'readyToGetInvData_';
    public $readToSendAntFlag = 'readyToGetInvData_readToSendAntFlag_';

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每月18号18点可以取上一个月全部数据
        //return '0 18 18 * *';
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

        for ($i = 1; $i <= 999999; $i++) {
            $limit = 1000;
            $offset = ($i - 1) * $limit;
            $list = AntAuthList::create()
                ->where('status', MaYiService::STATUS_3)
                ->limit($offset, $limit)->all();
            if (empty($list)) {
                break;
            }
            //可以取数了
            foreach ($list as $one) {
                $id = $one->getAttr('id');
                $suffix = $id % \App\Process\ProcessList\GetInvData::ProcessNum;
                //放到redis队列
                $key = $this->redisKey . $suffix;
                $redis->lPush($key, jsonEncode($one, false));
                $redis->hset($this->readToSendAntFlag, $this->readToSendAntFlag . $suffix, 1);
            }
        }

    }

    //通知蚂蚁
    function sendToAnt()
    {

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
