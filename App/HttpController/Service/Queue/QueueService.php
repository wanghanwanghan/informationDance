<?php

namespace App\HttpController\Service\Queue;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;

class QueueService extends ServiceBase
{
    use Singleton;

    private $redisDB = 15;

    function __construct()
    {
        return parent::__construct();
    }

    function pushJob(QueueConf $conf): bool
    {
        try {
            $redis = Redis::defer('redis');
            $redis->select($this->redisDB);
            $redis->set($conf->getJobId(), jsonEncode($conf->getJobData(), true), 3600);
            $redis->lPush($conf->getQueueListKey(), $conf->getJobId());
        } catch (\Throwable $e) {
            $filename = 'queue.log.' . date('Ymd', time());
            CommonService::getInstance()->log4PHP($e->getTraceAsString(), 'pushJob', $filename);
            return false;
        }

        return true;
    }

    function popJob(string $queueListKey): ?array
    {
        try {
            $redis = Redis::defer('redis');
            $redis->select($this->redisDB);
            $jobId = $redis->rPop($queueListKey);
            if ($jobId) {
                $data = $redis->get($jobId);
            } else {
                return null;
            }
        } catch (\Throwable $e) {
            $filename = 'queue.log.' . date('Ymd', time());
            CommonService::getInstance()->log4PHP($e->getTraceAsString(), 'popJob', $filename);
            return null;
        }

        return jsonDecode($data);
    }


}
