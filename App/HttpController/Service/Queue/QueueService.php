<?php

namespace App\HttpController\Service\Queue;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;

class QueueService extends ServiceBase
{
    use Singleton;

    private $redisDB = 14;

    function __construct()
    {
        return parent::__construct();
    }

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    //job入队列，左进
    function pushJob(QueueConf $conf): bool
    {
        try {
            $redis = Redis::defer('redis');
            $redis->select($this->redisDB);
            $redis->lPush($conf->getQueueListKey(), jsonEncode($conf->getJobData()));
        } catch (\Throwable $e) {
            return false;
        }

        return true;
    }

    //job出队列，右出
    function popJob(string $queueListKey): ?array
    {
        try {
            $redis = Redis::defer('redis');
            $redis->select($this->redisDB);
            $data = $redis->rPop($queueListKey);
        } catch (\Throwable $e) {
            return null;
        }

        return jsonDecode($data);
    }


}
