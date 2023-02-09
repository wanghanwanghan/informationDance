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

            $str = fgets($fr);
            $str = str_replace('"', '', $str);
            $str = trim($str);

            $arr = explode("\t", $str);

            if (count($arr) !== 8 || empty($arr) || strlen($arr[7]) !== 18) {
                continue;
            }

            $sql = <<<EOF
SELECT
	*
FROM
	company_search_guest_h_202301 
WHERE
	UNISCID = '{$arr[7]}' 
	AND NIC_ID IS NOT NULL 
	AND NIC_ID <> ''
EOF;
            $data = \wanghanwanghan\someUtils\moudles\laravelDB\laravelDB::getInstance([
                'business_base' => [
                    'driver' => 'mysql',
                    'host' => 'rm-2ze1hvx2ot36cq7l2io.mysql.rds.aliyuncs.com',
                    'port' => '3306',
                    'database' => 'business_base',
                    'username' => 'mrxd_root',
                    'password' => 'zbxlbj@2018*()',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_general_ci',
                    'strict' => false,
                    'prefix' => '',
                ],
            ])->connection('business_base')->select($sql);

            if (!empty($data)) {
                foreach ($data as $row) {
                    $content = [
                        $this->do_strtr($row->ENTNAME),
//                        $this->do_strtr($row->REGNO),
//                        $this->do_strtr($row->NACAOID),
//                        $this->do_strtr($row->NAME),
//                        $this->do_strtr($row->NAMETITLE),
//                        $this->do_strtr($row->ENTTYPE),
//                        $this->do_strtr($row->ESDATE),
//                        $this->do_strtr($row->APPRDATE),
//                        $this->do_strtr($row->ENTSTATUS),
//                        $this->do_strtr($row->REGCAP),
//                        $this->do_strtr($row->REGCAP_NAME),
//                        $this->do_strtr($row->REGCAPCUR),
//                        $this->do_strtr($row->RECCAP),
//                        $this->do_strtr($row->REGORG),
//                        $this->do_strtr($row->OPFROM),
//                        $this->do_strtr($row->OPTO),
//                        $this->do_strtr($row->OPSCOPE),
//                        $this->do_strtr($row->DOM),
//                        $this->do_strtr($row->DOMDISTRICT),
//                        $this->do_strtr($row->NIC_ID),
//                        $this->do_strtr($row->CANDATE),
//                        $this->do_strtr($row->REVDATE),
//                        $this->do_strtr($row->updated),
//                        $this->do_strtr($row->nic_full_name),
//                        $this->do_strtr($row->gong_si_jian_jie),
//                        $this->do_strtr($row->gao_xin_ji_shu),
//                        $this->do_strtr($row->deng_ling_qi_ye),
//                        $this->do_strtr($row->tuan_dui_ren_shu),
//                        $this->do_strtr($row->tong_xun_di_zhi),
//                        $this->do_strtr($row->web),
//                        $this->do_strtr($row->yi_ban_ren),
//                        $this->do_strtr($row->shang_shi_xin_xi),
//                        $this->do_strtr($row->app),
//                        $this->do_strtr($row->manager),
//                        $this->do_strtr($row->inv),
//                        $this->do_strtr($row->ying_shou_gui_mo),
//                        $this->do_strtr($row->ying_shou_gui_mo_2021),
//                        $this->do_strtr($row->na_shui_gui_mo_2021),
//                        $this->do_strtr($row->li_run_gui_mo_2021),
//                        $this->do_strtr($row->email),
//                        $this->do_strtr($row->wu_liu_xin_xi),
//                        $this->do_strtr($row->szjjcy),
//                        $this->do_strtr($row->zlxxcy),
//                        $this->do_strtr($row->app_data),
//                        $this->do_strtr($row->shang_pin_data),
//                        $this->do_strtr($row->report_year),
//                        $this->do_strtr($row->iso),
//                        $this->do_strtr($row->jin_chu_kou),
//                        $this->do_strtr($row->location),
//                        $this->do_strtr($row->iso_tags),
                    ];
                    fwrite($fw, implode('|', $arr) . '|' . implode('|', $content) . PHP_EOL);
                }
            } else {
                fwrite($fw, implode('|', $arr) . PHP_EOL);
            }

        }

        dd('over');

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
