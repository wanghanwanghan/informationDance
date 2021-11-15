<?php

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    protected function run($arg)
    {
        $page = 1;

        while (true) {

            $list = \App\HttpController\Models\EntDb\EntDbNacaoBasic::create()
                ->page($page, 500)->order('entid', 'asc')->all();

            CommonService::getInstance()->log4PHP("处理到了第{$page}页", 'info', 'P_class_.log');

            if (empty($list)) break;

            foreach ($list as $one) {

                $UNISCID = trim($one->UNISCID);

                if (empty($UNISCID)) continue;

                $type = \wanghanwanghan\someUtils\moudles\nsrsbh\nsrsbhToType::getInstance()
                    ->setNsrsbh($UNISCID)->getType();

                if (empty($type)) continue;

                $one->update(['JGLX' => trim($type, '-')]);

            }

            $page++;
        }

        CommonService::getInstance()->log4PHP('处理完成', 'info', 'nacao_basic.log');
    }

    function writeErr(\Throwable $e): void
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content, 'info', 'nacao_basic.log');
    }

    protected function onShutDown()
    {
        CommonService::getInstance()->log4PHP('onShutDown', 'info', 'nacao_basic.log');
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $this->writeErr($throwable);
    }
}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new P($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
