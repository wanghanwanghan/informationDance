<?php

namespace App\Process\ProcessList;

use App\Process\ProcessBase;
use Swoole\Process;
use Swoole\Coroutine;

class test_new_create extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
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
