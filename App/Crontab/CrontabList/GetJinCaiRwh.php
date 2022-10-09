<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
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

    // 最后校正一次addTask返回的结果，因为有的会返回失败
    private function retryAddTask(array $arr)
    {
        $target = AntAuthList::create()->where('socialCredit', $arr['socialCredit'])->get();
        $ywBody = [
            'cxlx' => trim($arr['cxlx']),// 查询类型 0销项 1 进项
            'kprqq' => date('Y-m-d', $arr['kprqq']),// 开票日期起
            'kprqz' => date('Y-m-d', $arr['kprqz']),// 开票日期止
            'nsrsbh' => $arr['socialCredit'],// 纳税人识别号
        ];
        for ($try = 3; $try--;) {
            // 发送 试3次
            $addTaskInfo = (new JinCaiShuKeService())->addTask(
                $arr['socialCredit'],
                $target->getAttr('province'),
                $target->getAttr('city'),
                $ywBody
            );
            if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
                // 如果成功了
                break;
            }
            \co::sleep(60);
        }
        try {
            JinCaiTrace::create()->where('id', $arr['id'])->update([
                'code' => $addTaskInfo['code'] ?? '未返回',
                'province' => $addTaskInfo['result']['province'] ?? '未返回',
                'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
                'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
                'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
                'isComplete' => 0,
            ]);
        } catch (\Throwable $e) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
            CommonService::getInstance()->log4PHP($content, 'retryAddTask', 'GetJinCaiRwh.log');
        }
    }

    function run(int $taskId, int $workerIndex)
    {
        // 等待金财任务执行1小时后，开始取任务号

        return;

        $check = $this->crontabBase->withoutOverlapping(self::getTaskName(), 3600);

        if (!$check) return;

        $time = Carbon::now()->subHours(1)->timestamp;

        $page = 1;

        while (true) {

            $list = JinCaiTrace::create()
                ->where('updated_at', $time, '<')// 1小时前的所有任务
                ->where('isComplete', 0)
                ->where('traceNo', '未返回', '=', 'OR')// 通过这个条件触发retryAddTask
                ->page($page, 100)->all();

            if (empty($list)) break;

            foreach ($list as $rwh_list) {

                if ($rwh_list->getAttr('traceNo') === '未返回') {
                    // 重新处理未返回的情况
                    $this->retryAddTask(obj2Arr($rwh_list));
                    continue;
                }

                // 拿任务号
                $rwh_info = (new JinCaiShuKeService())
                    ->obtainResultTraceNo($rwh_list->getAttr('traceNo'));

                // 用来判断是否traceNo已经不用循环了
                // taskStatus 2是成功 0和1是任务还没开始采集 3是失败
                $taskStatus_0_1 = $taskStatus_3 = [];

                foreach ($rwh_info['result'] as $rwh_one) {

                    try {
                        // mysql中有无
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
                    $created_at = $rwh_list->getAttr('created_at');
                    $updated_at = $rwh_list->getAttr('updated_at');
                    $day = Carbon::createFromTimestamp($created_at)->diffInHours();
                    $hours = Carbon::createFromTimestamp($updated_at)->diffInHours();
                    if ($day < 48) {
                        // created_at 在2天内才刷新
                        if ($hours > 6) {
                            // updated_at 超过6小时了就刷新一次
                            $refreshTask = (new JinCaiShuKeService())->refreshTask($traceNo);
                            $rwh_list->update(['isComplete' => 0, 'updated_at' => time()]);
                        }
                    } else {
                        // 2天过后不刷新了
                        $rwh_list->update(['isComplete' => 2, 'updated_at' => time()]);
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
