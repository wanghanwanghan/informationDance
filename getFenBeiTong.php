<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\Api\JinCaiTrace;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\OSS\OSSService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use wanghanwanghan\someUtils\control;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class getFenBeiTong extends AbstractProcess
{
    function do_strtr(?string $str): string
    {
        $str = strtr($str, [
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
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => '', "\r\n" => '', "\r" => '', "\n" => '', "\t" => ''
        ]);
        return trim($str);
    }

    protected function run($arg)
    {
        $list = [
//            '91110108MA00654L08',
//            '91110105MA00823H4A',
//            '91110105MA004MNF8T',
//            '91110111MA0076A807',
//            '91110108MA00FP4F5A',
//            '91460100MAA8YF8T7L',
//            '91320594088140947F',
//            '91640500MA774K1K2E',
//            '913205943021120597',
//            '91320594MA21M50H4M',
//            '91320594MA26UTJJ53',
            '91320913MA1MA43G41',
//            '911101080628135175',
        ];

        foreach ($list as $code) {
            $filename_main = $code . '_main.txt';
            $filename_detail = $code . '_detail.txt';
            //取全部发票写入文件
            $id = 0;
            while (true) {
                echo $id . PHP_EOL;
                $list = EntInvoice::create()
                    ->addSuffix($code, 'test')
                    ->where('nsrsbh', $code)
                    ->where('id', $id, '>')
                    ->field([
                        'id',
                        'fpdm',//
                        'fphm',//
                        'kplx',//
                        'xfsh',//
                        'xfmc',//
                        'xfdzdh',//
                        'xfyhzh',//
                        'gfsh',//
                        'gfmc',//
                        'gfdzdh',//
                        'gfyhzh',//
                        'kpr',//
                        'skr',//
                        'fhr',//
                        'yfpdm',
                        'yfphm',
                        'je',//
                        'se',//
                        'jshj',//
                        'bz',//
                        'zfbz',//
                        'zfsj',//
                        'kprq',//
                        'fplx',//
                        'fpztDm',//
                        'slbz',
                        'rzdklBdjgDm',
                        'rzdklBdrq',
                        'direction',
                        'nsrsbh',
                    ])->limit(1000)->all();
                //没有数据了
                if (empty($list)) break;
                foreach ($list as $oneInv) {
                    $id = $oneInv->getAttr('id');
                    // 写主票
                    $main = array_map(function ($row) {
                        return str_replace('|', '', trim($row));
                    }, obj2Arr($oneInv));
                    file_put_contents($filename_main, implode('|', $main) . PHP_EOL, FILE_APPEND);
                    $detail = EntInvoiceDetail::create()
                        ->addSuffix($oneInv->getAttr('fpdm'), $oneInv->getAttr('fphm'), 'test')
                        ->where(['fpdm' => $oneInv->getAttr('fpdm'), 'fphm' => $oneInv->getAttr('fphm')])
                        ->field([
                            'spbm',//
                            'mc',//
                            'jldw',//
                            'shul',//
                            'je',//
                            'sl',//
                            'se',//
                            'mxxh',//
                            'dj',//
                            'ggxh',//
                        ])->all();
                    // 写明细
                    if (!empty($detail)) {
                        foreach ($detail as $fpxx) {
                            $info = array_map(function ($row) {
                                return str_replace('|', '', trim($row));
                            }, obj2Arr($fpxx));
                            file_put_contents($filename_detail, implode('|', $info) . PHP_EOL, FILE_APPEND);
                        }
                    }
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
    $process = new getFenBeiTong($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
