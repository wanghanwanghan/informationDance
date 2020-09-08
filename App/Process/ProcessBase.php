<?php

namespace App\Process;

use EasySwoole\Component\Process\AbstractProcess;
use Swoole\Process;

class ProcessBase extends AbstractProcess
{
    //阻塞操作可以用自定义进程

    protected function run($arg)
    {
    }

    protected function onPipeReadable(Process $process)
    {
        //这里不能接收数据，数据只能被接口一次
        //这里接收了，子类中就会接收失败
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }
}
