<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use Swoole\Process;

class Docx2Doc extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //接收参数可以是字符串也可以是数组

        CommonService::getInstance()->log4PHP(__CLASS__ . ' 启动');
    }

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        try
        {
            //接收数据 string
            $filename = $process->read().'.docx';

            file_get_contents("http://127.0.0.1:8992/single/{$filename}");

        }catch (\Throwable $e)
        {
            CommonService::getInstance()->log4PHP($e->getMessage());
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
