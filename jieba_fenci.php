<?php

use App\HttpController\Service\CreateConf;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;
use Swoole\Coroutine;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    function strtr_fun($str): string
    {
        $arr = [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ];

        return strtr($str, $arr);
    }

    protected function run($arg)
    {
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

            $insert = [];

            foreach ($jieba as $one) {
                $insert[] = [
                    'entname' => $entname,
                    'jieba' => $one,
                ];
            }

            var_dump($insert);

            jieba_model::create()->addSuffix(ord($entname) % 20)
                ->data($insert)
                ->save();

            $i++;

            if ($i % 100000 === 0) {
                \App\HttpController\Service\Common\CommonService::getInstance()->log4PHP(
                    "已经处理到了第 {$i} 行", 'info', 'jieba.log'
                );
            }

        }
    }

    function writeErr(\Throwable $e): void
    {

    }

    protected function onShutDown()
    {

    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $this->writeErr($throwable);
    }
}

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);

//orm
$config = new \EasySwoole\ORM\Db\Config();

//数据库配置
$config->setHost('rm-2ze5r17pbzd3l7rak.mysql.rds.aliyuncs.com');
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
    var_dump('exit jieba');
    die();
}
