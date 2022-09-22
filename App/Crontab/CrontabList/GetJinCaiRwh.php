<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\JinCaiRwh;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class GetJinCaiRwh extends AbstractCronTask
{
    public $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        // 等待金财任务执行1小时后，开始取任务号

        $check = $this->crontabBase->withoutOverlapping(self::getTaskName(), 3600);

        if (!$check) return;

        $time = Carbon::now()->subHours(1)->timestamp;

        $page = 1;

        while (true) {

            $list = JinCaiTrace::create()
                ->where('updated_at', $time, '<')
                ->where(['isComplete' => 0, 'code' => 'S000'])
                ->page($page, 200)->all();

            if (empty($list)) break;

            foreach ($list as $rwh_list) {

                $rwh_info = (new JinCaiShuKeService())
                    ->obtainResultTraceNo($rwh_list->getAttr('traceNo'));

                CommonService::getInstance()->log4PHP($rwh_info, 'rwh_info', 'GetJinCaiRwh.log');
                CommonService::getInstance()->log4PHP($rwh_list, 'rwh_info', 'GetJinCaiRwh.log');

                foreach ($rwh_info['result'] as $rwh_one) {

                    try {
                        $wupanTraceNo = $rwh_one['wupanTraceNo'];
                        $check = JinCaiRwh::create()->where('wupanTraceNo', $wupanTraceNo['wupanTraceNo'])->get();
                        if (empty($check)) {
                            JinCaiRwh::create()->data([
                                'taskStatus' => $wupanTraceNo['taskStatus'] ?? '未返回',
                                'traceNo' => $rwh_list->getAttr('traceNo'),
                                'wupanTraceNo' => $wupanTraceNo['wupanTraceNo'] ?? '未返回',
                            ])->save();
                        }
                    } catch (\Throwable $e) {
                        $file = $e->getFile();
                        $line = $e->getLine();
                        $msg = $e->getMessage();
                        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                        CommonService::getInstance()->log4PHP($content, 'try-catch', 'GetJinCaiRwh.log');
                        continue;
                    }

                }

            }

            $page++;

        }

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content, 'error', 'GetJinCaiRwh.log');
    }

}
