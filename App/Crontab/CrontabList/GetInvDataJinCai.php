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

class GetInvDataJinCai extends AbstractCronTask
{
    public $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        // 每月17号取
        return '47 19 21 * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $page = 1;

        while (true) {

            $list = AntAuthList::create()->where([
                'status' => MaYiService::STATUS_4,// 实际上是3
                'getDataSource' => 2
            ])->page($page, 100)->all();

            if (empty($list)) break;

            foreach ($list as $target) {

                // 首先判断是不是全电 取不到票
                if ($target->getAttr('isElectronics') === '全电试点企业') {
                    continue;
                }

                // 更新状态
                if ($target->getAttr('isElectronics') === '') {
                    // 取状态
                    $isElectronics = (new JinCaiShuKeService())
                        ->S000502($target->getAttr('socialCredit'));
                    $isElectronics = $isElectronics['msg'] ?? '';
                    // 更新
                    $target->update(['isElectronics' => $isElectronics]);
                    if ($isElectronics === '全电试点企业') continue;
                }

                // 开票日期止
                $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

                // 最大取票月份
                $big_kprq = $target->getAttr('big_kprq');

                // 开票日期起
                if ($big_kprq - 0 === 0) {
                    $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
                } else {
                    $kprqq = Carbon::createFromTimestamp($big_kprq)->subMonths(1)->startOfMonth()->timestamp;
                }

                // 拼task请求参数
                for ($cxlx = 2; $cxlx--;) {

                    $ywBody = [
                        'cxlx' => trim($cxlx),//查询类型 0销项 1 进项
                        'kprqq' => date('Y-m-d', $kprqq),//开票日期起
                        'kprqz' => date('Y-m-d', $kprqz),//开票日期止
                        'nsrsbh' => $target->getAttr('socialCredit'),//纳税人识别号
                    ];

                    // 发送
                    $addTaskInfo = (new JinCaiShuKeService())->addTask(
                        $target->getAttr('socialCredit'),
                        $target->getAttr('province'),
                        $ywBody
                    );

                    $data = jsonDecode(base64_decode($addTaskInfo['data']));

                    JinCaiTrace::create()->data([
                        'entName' => $target->getAttr('entName'),
                        'socialCredit' => $target->getAttr('socialCredit'),
                        'success' => $addTaskInfo['success'] ?? '未返回',
                        'code' => $addTaskInfo['code'] ?? '未返回',
                        'trace' => $addTaskInfo['trace'] ?? '未返回',
                        'type' => 1,// 无盘
                        'province' => $data['province'] ?? '未返回',
                        'taskCode' => $data['taskCode'] ?? '未返回',
                        'taskStatus' => $data['taskStatus'] ?? '未返回',
                        'traceNo' => $data['traceNo'] ?? '未返回',
                        'kprqq' => $kprqq,
                        'kprqz' => $kprqz,
                        'cxlx' => $cxlx,
                    ])->save();

                }


                //
                //
                //
                //
                //
                //


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
        CommonService::getInstance()->log4PHP($content, 'error', 'GetInvDataJinCai.log');
    }

}
