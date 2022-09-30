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
        return '*/2 * * * * ';
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
                ->where('updated_at', $time, '<')// 1小时前的所有任务
                ->where('isComplete', 0)
                ->page($page, 100)->all();

            if (empty($list)) break;

            foreach ($list as $rwh_list) {

                // 拿任务号
                $rwh_info = (new JinCaiShuKeService())
                    ->obtainResultTraceNo($rwh_list->getAttr('traceNo'));

                // 用来判断是否traceNo已经不用循环了
                // taskStatus 2是成功 0和1是任务还没开始采集 3是失败
                $taskStatus_0_1 = $taskStatus_3 = [];

                foreach ($rwh_info['result'] as $rwh_one) {

                    try {
                        $check = JinCaiRwh::create()
                            ->where('wupanTraceNo', $rwh_one['wupanTraceNo'])
                            ->get();
                        if (empty($check)) {
                            // 开始无盘任务号入库
                            JinCaiRwh::create()->data([
                                'taskStatus' => $rwh_one['taskStatus'] ?? '未返回',
                                'traceNo' => $rwh_list->getAttr('traceNo'),
                                'wupanTraceNo' => $rwh_one['wupanTraceNo'] ?? '未返回',
                            ])->save();
                        } else {
                            $check->update(['taskStatus' => $rwh_one['taskStatus'] ?? '未返回']);
                        }
                        if ($rwh_one['taskStatus'] - 0 === 1 || $rwh_one['taskStatus'] - 0 === 0) {
                            $taskStatus_0_1[] = $rwh_one['wupanTraceNo'];
                        }
                        if ($rwh_one['taskStatus'] - 0 === 3) {
                            $taskStatus_3[] = $rwh_one['wupanTraceNo'];
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

                // 所有rwh都正常
                if (empty($taskStatus_0_1) && empty($taskStatus_3)) {
                    $rwh_list->update(['isComplete' => 1]);
                } else {
                    // 如果traceNo已经采集很久，还是有未开始的，或者失败的，应该retry一下
                    $traceNo = $rwh_list->getAttr('traceNo');
                    $updated_at = $rwh_list->getAttr('updated_at');
                    $hours = Carbon::createFromTimestamp($updated_at)->diffInHours();
                    if ($hours > 15) {
                        // 超过15小时了
                        $refreshTask = (new JinCaiShuKeService())->refreshTask($traceNo);
                        $rwh_list->update(['updated_at' => time()]);
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
        CommonService::getInstance()->log4PHP($content, 'onException', 'GetJinCaiRwh.log');
    }

}
