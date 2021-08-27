<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;

class GetInvData extends ProcessBase
{
    public $p_index;
    public $redisKey;
    public $taxNo = '140301321321333';//91110108MA01KPGK0L

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //获取注册进程名称
        $name = $this->getProcessName();
        preg_match_all('/\d+/', $name, $all);
        $this->p_index = current(current($all)) - 0;
        //要消费的队列名
        $this->redisKey = 'readyToGetInvData_' . $this->p_index;

        $redis = Redis::defer('redis');
        $redis->select(15);

        while (true) {
            $entInRedis = $redis->rPop($this->redisKey);
            if (empty($entInRedis)) {
                mt_srand();
                \co::sleep(mt_rand(50, 100));
                continue;
            }
            $this->getDataByEle(jsonDecode($entInRedis));
        }
    }

    //01增值税专用发票
    //02货运运输业增值税专用发票
    //03机动车销售统一发票
    //04增值税普通发票
    //10增值税普通发票电子
    //11增值税普通发票卷式
    //14通行费电子票
    //15二手车销售统一发票

    function getDataByEle($entInfo): bool
    {
        if (empty($entInfo)) {
            return false;
        }

        $KPKSRQ = Carbon::now()->subMonths(23)->startOfMonth()->format('Y-m-d');//开始日
        $KPJSRQ = Carbon::now()->subMonth()->endOfMonth()->format('Y-m-d');//截止日

        $KPKSRQ = '2020-01-01';
        $KPJSRQ = '2021-08-01';

        $NSRSBH = $entInfo['socialCredit'];

        $FPLXDMS = [
            '01', '02', '03', '04', '10', '11', '14', '15'
        ];

        //进项
        foreach ($FPLXDMS as $FPLXDM) {
            $KM = '1';
            for ($page = 1; $page <= 999999; $page++) {
                $res = (new DaXiangService())->getInv($this->taxNo, $page . '', $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);
                $content = jsonDecode(base64_decode($res['content']));
                if ($content['code'] === '0000' && !empty($content['data']['records'])) {
                    foreach ($content['data']['records'] as $row) {
                        $this->writeFile($row, $NSRSBH, 'in', $FPLXDM);
                    }
                } else {
                    $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
                    CommonService::getInstance()->log4PHP($content['msg'], $info, 'ant.log');
                    $this->writeFile([], $NSRSBH, 'in', $FPLXDM);
                    break;
                }
            }
        }

        //销项
        foreach ($FPLXDMS as $FPLXDM) {
            $KM = '2';
            for ($page = 1; $page <= 999999; $page++) {
                $res = (new DaXiangService())->getInv($this->taxNo, $page . '', $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);
                $content = jsonDecode(base64_decode($res['content']));
                if ($content['code'] === '0000' && !empty($content['data']['records'])) {
                    foreach ($content['data']['records'] as $row) {
                        $this->writeFile($row, $NSRSBH, 'out', $FPLXDM);
                    }
                } else {
                    $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
                    CommonService::getInstance()->log4PHP($content['msg'], $info, 'ant.log');
                    break;
                }
            }
        }

        return true;
    }

    function writeFile(array $row, string $NSRSBH, string $invType, string $FPLXDM): bool
    {
        $store = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;

        $filename = $NSRSBH . "_{$FPLXDM}_{$invType}.json";

        is_dir($store) || mkdir($store, 0644, true);

        if (empty($row)) {
            $content = '' . PHP_EOL;
        } else {
            $content = jsonEncode($row, false) . PHP_EOL;
        }

        file_put_contents($store . $filename, $content, FILE_APPEND | LOCK_EX);

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
