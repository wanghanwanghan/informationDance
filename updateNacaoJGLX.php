<?php

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    protected function run($arg)
    {
        while (true) {
            echo '123' . PHP_EOL;
            sleep(1);
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
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

//orm
$config = new \EasySwoole\ORM\Db\Config();

//数据库配置
$config->setHost('rm-2ze5r17pbzd3l7rakyo.mysql.rds.aliyuncs.com');
$config->setPort(3306);
$config->setUser('mrxd');
$config->setPassword('zbxlbj@2018*()');
$config->setDatabase('shang_pin_tiao_ma');
$config->setCharset('utf8mb4');

//链接池配置
$config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
$config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
$config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
$config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
$config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
$config->setAutoPing(5); //设置自动ping客户端链接的间隔

DbManager::getInstance()->addConnection(new Connection($config), 'SPTM');

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new P($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
