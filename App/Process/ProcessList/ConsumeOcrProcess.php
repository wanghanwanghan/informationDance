<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;

class ConsumeOcrProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //接收参数可以是字符串也可以是数组

        CommonService::getInstance()->log4PHP(__CLASS__ . ' 启动');

        $this->consume();
    }

    protected function consume()
    {
        //自定义进程不需要传参数，启动后就一直消费一个列队
        $redis = Redis::defer('redis');

        while (true)
        {
            $data = $redis->rPop('ocrQueue');

            if (empty($data)) \co::sleep(2);

            $data = jsonDecode($data);
        }
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
        CommonService::getInstance()->log4PHP(__CLASS__, $throwable->getMessage());
    }


}
