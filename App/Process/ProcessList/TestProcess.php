<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use Swoole\Process;

class TestProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
        var_dump($arg);
        CommonService::getInstance()->log4PHP(__CLASS__.'启动');
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        //接收数据 string
        $data = jsonDecode($process->read());

        foreach ($data as $key => $val) {
            CommonService::getInstance()->log4PHP("{$key} => {$val}");
        }

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
    }


}
