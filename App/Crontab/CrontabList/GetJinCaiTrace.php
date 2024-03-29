<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class GetJinCaiTrace extends AbstractCronTask
{
    public $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        // 每月20号取
        return '53 16 20 * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $page = 1;

        while (true) {

            $list = AntAuthList::create()
                ->where(['status' => MaYiService::STATUS_3, 'getDataSource' => 2])
                ->where('isElectronics', '%成功', 'LIKE')
                ->page($page, 100)
                ->all();

            if (empty($list)) break;

            // 开票日期起
            $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
            // 开票日期止
            $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

            foreach ($list as $target) {

                // 最大取票月份
                $big_kprq = $target->getAttr('big_kprq');

                // 本月取过了 不取了
                if ($kprqz === $big_kprq) {
                    continue;
                }

                // 开票日期起
                if ($big_kprq - 0 !== 0) {
                    $kprqq = Carbon::createFromTimestamp($big_kprq)->subMonths(1)->startOfMonth()->timestamp;
                }

                // 拼task请求参数
                $ywBody = [
                    'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
                    'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
                    'nsrsbh' => $target->getAttr('socialCredit'),// 纳税人识别号
                ];

                try {
                    for ($try = 3; $try--;) {
                        // 发送 试3次
                        $addTaskInfo = (new JinCaiShuKeService())->addTask(
                            $target->getAttr('socialCredit'),
                            $target->getAttr('province'),
                            $target->getAttr('city'),
                            $ywBody
                        );
                        if (isset($addTaskInfo['code']) && strlen($addTaskInfo['code']) > 1) {
                            break;
                        }
                        // 还要间隔2分钟
                        \co::sleep(120);
                    }
                    JinCaiTrace::create()->data([
                        'entName' => $target->getAttr('entName'),
                        'socialCredit' => $target->getAttr('socialCredit'),
                        'code' => $addTaskInfo['code'] ?? '未返回',
                        'type' => 1,// 无盘
                        'province' => $addTaskInfo['result']['province'] ?? '未返回',
                        'taskCode' => $addTaskInfo['result']['taskCode'] ?? '未返回',
                        'taskStatus' => $addTaskInfo['result']['taskStatus'] ?? '未返回',
                        'traceNo' => $addTaskInfo['result']['traceNo'] ?? '未返回',
                        'kprqq' => $kprqq,
                        'kprqz' => $kprqz,
                    ])->save();
                    // 还要间隔2分钟
                    \co::sleep(120);
                } catch (\Throwable $e) {
                    $file = $e->getFile();
                    $line = $e->getLine();
                    $msg = $e->getMessage();
                    $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
                    CommonService::getInstance()->log4PHP($content, 'try-catch', 'GetJinCaiTrace.log');
                    continue;
                }

            }

            $page++;

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content, 'onException', 'GetJinCaiTrace.log');
    }

}
