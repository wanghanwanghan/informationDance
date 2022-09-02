<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\UserApproximateEnterpriseModel;
use App\HttpController\Service\XinDong\Score\qpf;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;

class MatchSimilarEnterprisesProccess extends ProcessBase
{
    const ProcessNum = 3;
    const QueueKey = 'MatchSimilarEnterprisesQueue';

    public $p_index;

    //匹配近似企业

    protected function run($arg)
    {
        parent::run($arg);

        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;

        $redis = Redis::defer('redis');
        $redis->select(15);

        //开始消费
        while (true) {

            $entInRedis = $redis->rPop(self::QueueKey);

            if (empty($entInRedis)) {
                mt_srand();
                \co::sleep(2);
                continue;
            }

            $info = jsonDecode($entInRedis);

            $score = (new qpf(
                ...$info['baes'],
                $info['ys_label'], $info['NIC_ID'], $info['ESDATE'], $info['DOMDISTRICT']
            ))->expr();

            UserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                'userid' => $info['user_id'],
                'companyid' => $info['companyid'],
                'esid' => '',
                'score' => $score,
                'mvcc' => '',
            ])->save();

        }

    }

    protected function onPipeReadable(Process $process): bool
    {
        parent::onPipeReadable($process);
        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }
}
