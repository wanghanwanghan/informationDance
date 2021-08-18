<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;

class PackAuthBookProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        while (true) {
            CommonService::getInstance()->log4PHP(Carbon::now()->format('Y-m-d H:i:s'), 'info', 'ant.log');
            \co::sleep(10);
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
    }


}
