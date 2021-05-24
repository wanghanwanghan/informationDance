<?php

namespace App\Process\Service;

use App\HttpController\Service\ServiceBase;
use App\Process\ProcessList\ConsumeOcrProcess;
use App\Process\ProcessList\Docx2Doc;
use App\Process\ProcessList\TestProcess;
use EasySwoole\Component\Di;
use EasySwoole\Component\Process\Config;
use EasySwoole\Component\Process\Manager;
use EasySwoole\Component\Singleton;

class ProcessService extends ServiceBase
{
    use Singleton;

    //总共创建了几个进程 [进程名 => 数量] 数量是从0开始的，代表第一个进程
    public $processNo = [];

    //只能在mainServerCreate中用
    public function create($funcName = '', $arg = ['foo' => 'bar'], $processNum = 1): bool
    {
        return empty($funcName) ?: $this->$funcName($arg, $processNum);
    }

    //给进程发参数
    function sendToProcess(string $name, string $arg)
    {
        try {
            mt_srand();
            $name .= mt_rand(0, $this->processNo[$name]);
            $processService = Di::getInstance()->get($name);
            $process = $processService->getProcess($name);
            return $process->write($arg);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }
    }

    //创建进程
    private function test($arg, $processNum): bool
    {
        //创建进程名
        $processName = __FUNCTION__;

        $this->processNo[$processName] = -1;

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
            //
            $this->processNo[$processName]++;
        }

        return true;
    }

    //创建进程
    private function docx2doc($arg, $processNum): bool
    {
        //创建进程名
        $processName = __FUNCTION__;

        $this->processNo[$processName] = -1;

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
            Di::getInstance()->set($processName . $i, new Docx2Doc($processConfig));
            //创建进程
            Manager::getInstance()->addProcess(Di::getInstance()->get($processName . $i));
            //
            $this->processNo[$processName]++;
        }

        return true;
    }

    //创建进程
    private function consumeOcr($arg, $processNum): bool
    {
        //创建进程名
        $processName = __FUNCTION__;

        $this->processNo[$processName] = -1;

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
            Di::getInstance()->set($processName . $i, new ConsumeOcrProcess($processConfig));
            //创建进程
            Manager::getInstance()->addProcess(Di::getInstance()->get($processName . $i));
            //
            $this->processNo[$processName]++;
        }

        return true;
    }

    //创建进程
    private function wanbaochui($arg, $processNum): bool
    {
        //创建进程名
        $processName = __FUNCTION__;

        $this->processNo[$processName] = -1;

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
            Di::getInstance()->set($processName . $i, new ConsumeOcrProcess($processConfig));
            //创建进程
            Manager::getInstance()->addProcess(Di::getInstance()->get($processName . $i));
            //
            $this->processNo[$processName]++;
        }

        return true;
    }


}
