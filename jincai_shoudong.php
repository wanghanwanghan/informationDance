<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class jincai_shoudong extends AbstractProcess
{
    protected function run($arg)
    {
        $this->addTask();

    }

    function addTask()
    {
        $list = \App\HttpController\Models\Api\AntAuthList::create()
            ->where('getDataSource', 2)
            ->where('status', 3)
            ->where('isElectronics', '%信息成功%', 'like')
            ->all();

        foreach ($list as $target) {

            // 开票日期止
            $kprqz = Carbon::now()->subMonths(1)->endOfMonth()->timestamp;

            // 最大取票月份
            $big_kprq = $target->getAttr('big_kprq');

            // 本月取过了 不取了
            if ($kprqz === $big_kprq) {
                continue;
            }

            // 开票日期起
            if ($big_kprq - 0 === 0) {
                $kprqq = Carbon::now()->subMonths(23)->startOfMonth()->timestamp;
            } else {
                $kprqq = Carbon::createFromTimestamp($big_kprq)->subMonths(1)->startOfMonth()->timestamp;
            }

            // 拼task请求参数
            for ($cxlx = 2; $cxlx--;) {

                $ywBody = [
                    'cxlx' => trim($cxlx),// 查询类型 0销项 1 进项
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
                        'cxlx' => $cxlx,
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

        }
    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {

    }
}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);

//mysql pool
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlPoolForRDS3NicCode::getInstance()->createMysql();
CreateMysqlPoolForRDS3SiJiFenLei::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new jincai_shoudong($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
