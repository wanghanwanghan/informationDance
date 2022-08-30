<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\DianZiqian\DianZiQianService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class RunCheckCapital extends AbstractCronTask
{
    public $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每分钟执行一次
        return '0 10/14 * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $DianZiQianService = new DianZiQianService();
        $path      = '/open-api/trade/accountInfo';
        $param     = $DianZiQianService->buildParam([], $path);
        $resp      = (new CoHttpClient())
            ->useCache($DianZiQianService->curl_use_cache)
            ->send($DianZiQianService->url . $path, $param,[], ['enableSSL' => true], 'GET');
        if($resp['description'] == 'success'){
            dingAlarmAtUser('本公司在电子牵的账户信息',
                            [
                                '累计充值下单金额总额' => $resp['data']['totalAmount'],
                                '已使用金额总额' => $resp['data']['usedAmount'],
                                '目前可用金额' => $resp['data']['availableAmount'],
                            ],
                            ['lemon1968']
            );
        }

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'info', 'CrontabList_RunCheckCapital');
    }
}