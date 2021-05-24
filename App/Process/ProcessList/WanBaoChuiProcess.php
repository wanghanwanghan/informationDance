<?php

namespace App\Process\ProcessList;

use App\Process\ProcessBase;
use Swoole\Process;

class WanBaoChuiProcess extends ProcessBase
{
    public $breakTime;

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        //接收数据 string
        $data = jsonDecode($process->read());

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }


}
