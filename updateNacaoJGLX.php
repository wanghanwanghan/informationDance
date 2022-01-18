<?php

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateRedisPool;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    protected function run($arg)
    {
        $redis = \EasySwoole\RedisPool\Redis::defer('redis');
        $redis->select(13);

        $fp = fopen('/mnt/tiaoma.log', 'r');

        $i = 1;

        // 0、1、2，其中 0 是提取关键字，1 是切字分词， 2 是获取词性标注。

        while (feof($fp) === false) {

            $str = trim(fgets($fp));

            $arr = explode('|||', $str);

            if (count($arr) !== 3) {
                continue;
            }

            $entname = trim($arr[1]);
            $terms = trim($arr[2]);

            if (empty($entname) || empty($terms)) {
                continue;
            }

            $jieba = jieba($terms, 0);

            if (!empty($jieba)) {
                $jieba = array_map(function ($row) {
                    if (preg_match('/^[\x{4e00}-\x{9fa5}]+$/u', $row) > 0) {
                        return $row;
                    }
                    return null;
                }, $jieba);
            } else {
                continue;
            }

            $jieba = array_values(array_filter($jieba));

            if (empty($jieba)) {
                continue;
            }

            foreach ($jieba as $one) {
                //ZINCRBY '每日信动（北京）有限公司' 1 '衬衫'
                $redis->zInCrBy($entname, 1, $one);
            }

            $i++;

            if ($i % 100000 === 0) {
                $o_o = date('Y-m-d H:i:s', time()) . " 已经处理到了第 {$i} 行";
                file_put_contents(
                    '/home/wwwroot/informationDance/Static/Log/jieba.log', $o_o . PHP_EOL, FILE_APPEND
                );
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

//mysql
CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);
//CreateMysqlPoolForProjectDb::getInstance()->createMysql();
//CreateMysqlPoolForEntDb::getInstance()->createMysql();
//CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
//CreateMysqlOrm::getInstance()->createMysqlOrm();
//CreateMysqlOrm::getInstance()->createEntDbOrm();

//redis pool
CreateRedisPool::getInstance()->createRedis();

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new P($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
