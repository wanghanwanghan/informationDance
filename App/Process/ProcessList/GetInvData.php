<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;

class GetInvData extends ProcessBase
{
    public $p_index;
    public $redisKey;

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);
        // 获取注册进程名称
        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;
        $this->redisKey = 'readyToGetInvData_' . $this->p_index;
        // 获取进程实例 \Swoole\Process
        //$this->getProcess();
        // 获取当前进程Pid
        //$this->getPid();
        // 获取注册时传递的参数
        //$this->getArg();

        //01增值税专用发票
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票
        //10增值税普通发票电子
        //11增值税普通发票卷式
        //14通行费电子票
        //15二手车销售统一发票

        while (true) {

            \co::sleep(1800);

            $entCode = '140301321321333';
            $page = '1';
            $NSRSBH = '911199999999CN0008';
            $KM = '2';
            $FPLXDM = '10';
            $KPKSRQ = '2020-01-01';
            $KPJSRQ = '2021-08-01';

            $res = (new DaXiangService())->getInv($entCode, $page, $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);

            $content = jsonDecode(base64_decode($res['content']));

            if ($content['code'] === '0000' && !empty($content['data']['records'])) {
                foreach ($content['data']['records'] as $row) {
                    //$this->writeFile($row, $NSRSBH, 'out');
                }
            } else {
                $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
                CommonService::getInstance()->log4PHP($content['msg'], $info, 'ant.log');
            }

        }
    }

    function writeFile(array $row, string $NSRSBH, string $invType): bool
    {
        $store = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;

        $filename = $NSRSBH . "_{$invType}.json";

        is_dir($store) || mkdir($store, 0644, true);

        file_put_contents($store . $filename, jsonEncode($row, false) . PHP_EOL, FILE_APPEND | LOCK_EX);

        return true;
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
