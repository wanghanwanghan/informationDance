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
        return '*/15 * * * *';
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

        //判断 $this->readToSendAntFlag 里是不是都是0，0代表没有处理的任务
        while (true) {
            $flag_arr = [];
            $num = \App\Process\ProcessList\GetInvData::ProcessNum;
            for ($i = $num; $i--;) {
                $flag = $redis->hGet($this->readToSendAntFlag, $this->readToSendAntFlag . $num) - 0;
                $flag !== 0 ?: $flag_arr[] = $flag;
            }
            if (count($flag_arr) !== $num) {
                \co::sleep(3);
                continue;
            }
            $this->sendToAnt();
            break;
        }

        CommonService::getInstance()->log4PHP('通知蚂蚁完毕');
    }

    //通知蚂蚁
    function sendToAnt()
    {
        CommonService::getInstance()->log4PHP('正在通知蚂蚁');
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
