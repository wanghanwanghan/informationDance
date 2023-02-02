<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateMysqlPoolForJinCai;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class co_test extends AbstractProcess
{
    //启动
    protected function run($arg)
    {
        $fr = fopen('baber1.txt', 'r');
        $fw = fopen('ffffzzzdddzzzddd.txt', 'w+');

        $i = 1;

        while (feof($fr) === false) {

            echo $i . PHP_EOL;
            $i++;

            $str = trim(fgets($fr));
            $str = str_replace('"', '', $str);
            $str = trim($str);
            if (empty($str) || strlen($str) !== 18) {
                continue;
            }

            $sql = <<<EOF
SELECT
	companyid,
	ENTNAME,
	UNISCID 
FROM
	company_basic 
WHERE
	UNISCID = '{$str}' 
	AND NIC_ID IS NOT NULL 
	AND NIC_ID <> ''
EOF;
            $data = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
                'hd' => [
                    'driver' => 'mysql',
                    'host' => 'rm-2ze1hvx2ot36cq7l2io.mysql.rds.aliyuncs.com',
                    'port' => '3306',
                    'database' => 'hd_saic',
                    'username' => 'mrxd_root',
                    'password' => 'zbxlbj@2018*()',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                    'strict' => false,
                    'prefix' => '',
                ]
            ])->connection('hd')->select($sql);

            if (!empty($data)) {
                foreach ($data as $rrrrr) {
                    fwrite($fw, implode('|', [
                            $rrrrr->companyid,
                            trim($rrrrr->ENTNAME),
                            $rrrrr->UNISCID,
                        ]) . PHP_EOL);
                }
            }

        }


    }

    function getBidsResult_c($list)
    {
        $fw = fopen('zhaotoubiao_caigou.txt', 'w+');

        foreach ($list as $one) {

            $one = str_replace(',', '|', $one);

            $arr = explode('|', $one);

            $res = (new App\HttpController\Service\ShuMeng\ShuMengService())
                ->getBidsResult_c($arr[1], 1);

            if ($res['paging']['total'] === 0) {
                echo $one . '|0' . PHP_EOL;
                continue;
            }

            $page_total = $res['paging']['total'] / 10;

            for ($page = 1; $page <= $page_total; $page++) {

                $res = (new App\HttpController\Service\ShuMeng\ShuMengService())
                    ->getBidsResult_c($arr[1], $page);

                foreach ($res['result'] as $row) {

                    $content = [
                        $this->do_strtr($row['项目编号'] ?? ''),
                        $this->do_strtr($row['项目名称'] ?? ''),
                        $this->do_strtr($row['行政区域'] ?? ''),
                        $this->do_strtr($row['采购单位'] ?? ''),
                        $this->do_strtr($row['中标供应商'] ?? ''),
                        $this->do_strtr($row['中标金额'] ?? ''),
                        $this->do_strtr($row['代理机构'] ?? ''),
                        $page_total,
                        $page,
                    ];

                    echo $one . '|' . implode('|', $content) . PHP_EOL;
                    fwrite($fw, $one . '|' . implode('|', $content) . PHP_EOL);

                }

            }

        }
    }

    function do_strtr(?string $str): string
    {
        $str = str_replace(["\r\n", "\r", "\n", '|', "\t"], '', trim($str));

        return strtr($str, [
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
        ]);
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
CreateMysqlPoolForJinCai::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();
CreateMysqlOrm::getInstance()->createRDS3JinCai();

CreateRedisPool::getInstance()->createRedis();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new co_test($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    exit;
}
