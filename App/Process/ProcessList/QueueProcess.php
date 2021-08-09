<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\Queue\QueueConf;
use App\HttpController\Service\Queue\QueueService;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;
use Swoole\Coroutine;

class QueueProcess extends ProcessBase
{
    public $is_start = false;

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        $key = (new QueueConf())->getQueueListKey();

        while (true) {
            $data = QueueService::getInstance()->popJob($key);
            if (!$data) {
                break;
            }
            CommonService::getInstance()->log4PHP($data);
        }

        CommonService::getInstance()->log4PHP('queue完成');
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        return true;
    }

    function writeErr(\Throwable $e): void
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content);
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $this->writeErr($throwable);
    }


}
