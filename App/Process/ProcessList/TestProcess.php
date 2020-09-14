<?php

namespace App\Process\ProcessList;

use App\Process\ProcessBase;
use Swoole\Process;

class TestProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        //接收数据 string
        $commend = $process->read();
    }


}
