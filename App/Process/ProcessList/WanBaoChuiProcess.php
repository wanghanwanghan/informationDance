<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\Process\ProcessBase;
use Swoole\Process;

class WanBaoChuiProcess extends ProcessBase
{
    public $breakTime;

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        $res = (new CoHttpClient())->useCache(false)
            ->send('http://wbcapi.shuhuiguoyou.com/auction/0/?page=10', [], [
                'token' => 'df0b8ce22bb092a98a533f66705f50b7',
            ], [], 'post');

        CommonService::getInstance()->log4PHP($res);


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
