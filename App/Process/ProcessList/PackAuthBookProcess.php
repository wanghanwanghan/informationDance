<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;

class PackAuthBookProcess extends ProcessBase
{
    protected function run($arg)
    {
        //没发送的授权书，每月固定时间给大象发过去
        while (true) {
            \co::sleep(300);
            $date = Carbon::now()->format('Ym');
            $time = Carbon::now()->format('H');
            if (substr($date, -2) != 15 || $time != 23) {
                //每月15号23点发送
                continue;
            }
            $list = AntAuthList::create()->where([
                'sendDate' => 0,//未提交的
                'status' => 1,//pdf生成完毕
            ])->all();
            if (empty($list)) {
                \co::sleep(86400);
                continue;
            }
            CommonService::getInstance()->log4PHP(Carbon::now()->format('Y-m-d H:i:s'), 'info', 'ant.log');
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
