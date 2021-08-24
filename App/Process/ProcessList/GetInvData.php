<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use Swoole\Process;

class GetInvData extends ProcessBase
{
    public $p_index;

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
        // 获取注册进程名称
        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;
        CommonService::getInstance()->log4PHP($this->p_index);
        // 获取进程实例 \Swoole\Process
        //$this->getProcess();
        // 获取当前进程Pid
        //$this->getPid();
        // 获取注册时传递的参数
        //$this->getArg();
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
