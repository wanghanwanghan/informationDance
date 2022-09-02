<?php

namespace App\Process\ProcessList;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Models\Api\UserApproximateEnterpriseModel;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\XinDong\Score\qpf;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;
use wanghanwanghan\someUtils\control;

class MatchSimilarEnterprisesProccess extends ProcessBase
{
    const ProcessNum = 3;
    const QueueKey = 'MatchSimilarEnterprisesQueue';

    public $p_index;
    private $es_obj_time;//es对象有链接超时时间

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
                $info['base'][0], $info['base'][1], $info['base'][2], $info['base'][3],
                $info['ys_label'], $info['NIC_ID'], substr($info['ESDATE'], 0, 4), $info['DOMDISTRICT']
            ))->expr();

            $esid = control::getUuid();

            $this->toEs($esid, $info);

            try {
                UserApproximateEnterpriseModel::create()->addSuffix($info['user_id'])->data([
                    'userid' => $info['user_id'],
                    'companyid' => $info['companyid'],
                    'esid' => $esid,
                    'score' => $score,
                    'mvcc' => '',
                ])->save();
            } catch (\Throwable $e) {
                $file = $e->getFile();
                $line = $e->getLine();
                $msg = $e->getMessage();
                $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                CommonService::getInstance()->log4PHP($content);
            }

        }

    }

    private function toEs(string $esid, array $data)
    {
        if (empty($this->es_obj_time)) {
            $bean = new \EasySwoole\ElasticSearch\RequestBean\Get();
            $bean->setIndex('my-index');
            $bean->setType('my-type');
            $bean->setId('my-id');
            $obj = (new ElasticSearchService())->createSearchBean()->getBody();
        }


        //这里可以把搜客中的数据查出来(company_202209)，放到新的es库中
        go(function () use ($esid, $data) {
        });
    }

    protected function onPipeReadable(Process $process)
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
