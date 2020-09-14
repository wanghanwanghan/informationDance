<?php

namespace App\Process\Service;

use App\HttpController\Service\ServiceBase;
use App\Process\ProcessList\TestProcess;
use EasySwoole\Component\Di;
use EasySwoole\Component\Process\Config;
use EasySwoole\Component\Process\Manager;
use EasySwoole\Component\Singleton;

class ProcessService extends ServiceBase
{
    use Singleton;

    //只能在mainServerCreate中用
    public function create($funcName = '', $arg = ['a' => 5], $processNum = 1): bool
    {
        return empty($funcName) ?: $this->$funcName($arg, $processNum);
    }

    //给进程发参数
    function sendToProcess(string $name, string $arg)
    {
        $processService = Di::getInstance()->get($name);

        $process = $processService->getProcess($name);

        return $process->write($arg);
    }

    //创建进程
    private function test($arg, $processNum): bool
    {
        //创建进程名
        $processName = __FUNCTION__;

        //循环创建
        for ($i = $processNum; $i--;) {
            $processConfig = new Config();
            $processConfig->setProcessName($processName . $i);//设置进程名称
            $processConfig->setProcessGroup($processName . 'Group');//设置进程组
            $processConfig->setArg($arg);//传参
            $processConfig->setRedirectStdinStdout(false);//是否重定向标准io
            $processConfig->setPipeType($processConfig::PIPE_TYPE_SOCK_DGRAM);//设置管道类型
            $processConfig->setEnableCoroutine(true);//是否自动开启协程
            $processConfig->setMaxExitWaitTime(3);//最大退出等待时间
            //进ioc
            Di::getInstance()->set($processName . $i, new TestProcess($processConfig));
            //创建进程
            Manager::getInstance()->addProcess(Di::getInstance()->get($processName . $i));
        }

        return true;
    }

}
