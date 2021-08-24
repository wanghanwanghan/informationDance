<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class GetInvData extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP(Carbon::now()->format('Ymd'), 'GetInvDataCrontabRunAt', 'ant.log');

        //01增值税专用发票
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票
        //10增值税普通发票电子
        //11增值税普通发票卷式
        //14通行费电子票
        //15二手车

        $entCode = '140301321321333';
        $page = '1';
        $NSRSBH = '911199999999CN0008';
        $KM = '1';
        $FPLXDM = '04';
        $KPKSRQ = '2020-01-01';
        $KPJSRQ = '2021-01-01';

        $res = (new DaXiangService())->getInv($entCode, $page, $NSRSBH, $KM, $FPLXDM, $KPKSRQ, $KPJSRQ);

        $content = jsonDecode(base64_decode($res['content']));

        if ($content['code'] === '0000' && !empty($content['data']['records'])) {

        } else {
            $info = "{$NSRSBH} : page={$page} KM={$KM} FPLXDM={$FPLXDM} KPKSRQ={$KPKSRQ} KPJSRQ={$KPJSRQ}";
            CommonService::getInstance()->log4PHP($content['msg'], $info, 'ant.log');
        }

        CommonService::getInstance()->log4PHP($content);
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'GetInvDataCrontabException', 'ant.log');
    }


}
