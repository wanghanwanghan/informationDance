<?php

use QL\QueryList;
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
use App\HttpController\Service\HttpClient\CoHttpClient;

include './vendor/autoload.php';
include './bootstrap.php';

Core::getInstance()->initialize();

class QueryList1 extends AbstractProcess
{
    protected function run($arg)
    {
        $this->get_hg();
    }

    //海关列表
    function get_hg(): void
    {
        $url = "https://www.hsbianma.com/search?keywords=0504&filterFailureCode=true";

        $wholePage = QueryList::getInstance()->get($url);

        $table = $wholePage->find('tbody')->eq(0);

        // 采集表的每行内容
        $tableRows = $table->find('tr')->map(function ($row) {
            return $row->find('td')->texts()->all();
        });


        dd($tableRows);


    }

    function run_1()
    {
        $data = [
            'checkBeginDate' => '2011-01-01',
            'checkEndDate' => '2020-12-31',
            'establishDate' => [
                'from' => '1900-01-01',
                'to' => '9999-01-01',
            ],
            'fundType' => '创业投资',
            'offiProvinceFsc' => 'province',
            'orgForm' => '有限合伙企业',
            'primaryInvestType' => '私募股权、创业投资基金管理人',
            'regiProvinceFsc' => 'province',
            'registerDate' => [
                'from' => '2011-01-01',
                'to' => '2020-12-31',
            ],
        ];

        $fp = fopen('simu.csv', 'w+');

        for ($i = 0; $i <= 11; $i++) {
            $rand = mt_rand(1, 100000);
            $url = "https://gs.amac.org.cn/amac-infodisc/api/pof/manager/query?rand={$rand}&page={$i}&size=100";
            $res = (new CoHttpClient())
                ->useCache(false)
                ->setCheckRespFlag(false)
                ->send($url, empty($data) ? '{}' : $data, [], [], 'postjson');
            echo "数据行数:" . count($res['content']) . ' $i:' . $i . PHP_EOL;
            foreach ($res['content'] as $one) {
                $establishDate = $one['establishDate'];
                $registerDate = $one['registerDate'];
                if (is_numeric($establishDate) && strlen($establishDate) === 13) {
                    $one['establishDate'] = $this->msecdate($establishDate);
                }
                if (is_numeric($registerDate) && strlen($registerDate) === 13) {
                    $one['registerDate'] = $this->msecdate($registerDate);
                }
                $tmp = array_map(function ($row) {
                    return '|||' . str_replace(['，', ','], ' ', $row);
                }, $one);
                fwrite($fp, implode(',', $tmp) . PHP_EOL);
            }
        }

        fclose($fp);

        var_dump('wan cheng');
    }

    function run_2()
    {
        $fp = fopen('simuprolist.csv', 'w+');
        for ($i = 0; $i <= 2000; $i++) {
            $rand = mt_rand(1, 100000);
            $url = "https://gs.amac.org.cn/amac-infodisc/api/pof/fund?rand={$rand}&page={$i}&size=100";
            $res = (new CoHttpClient())
                ->useCache(false)
                ->setCheckRespFlag(false)
                ->send($url, empty($data) ? '{}' : $data, [], [], 'postjson');
            echo $i . PHP_EOL;
            if (empty($res['content'])) {
                var_dump($res);
                break;
            }
            foreach ($res['content'] as $one) {
                fwrite($fp, implode('|||', [$one['url'], $one['managerUrl']]) . PHP_EOL);
            }
        }
        fclose($fp);
        var_dump('wan cheng');
    }

    function ql_1()
    {
        // https://gs.amac.org.cn/amac-infodisc/res/pof/fund/1803191908100089.html
        $fp = fopen('simuprolist.csv', 'r');
        $detail_fp = fopen('simuprodetail.csv', 'w+');
        $detail_arr = [];
        $i = 1;
        while (feof($fp) === false) {
            echo $i . PHP_EOL;
            $row = fgets($fp);
            $arr = explode('|||', $row);
            $url = "https://gs.amac.org.cn/amac-infodisc/res/pof/fund/{$arr[0]}";
            $wholePage = QueryList::getInstance()->get($url);
            $table = $wholePage->find(".table-response>table")->eq(0);
            // 采集表的每行内容
            $tableRows = $table->find('tr')->map(function ($row) {
                return $row->find('td')->texts()->all();
            });
            if (!empty($tableRows)) {
                foreach ($tableRows as $key => $val) {
                    $name = rtrim(trim($val[0]), ':');
                    $content = trim($val[1]);
                    $detail_arr[$name] = empty($content) ? '--' : $content;
                }
                fwrite($detail_fp, implode('|||', $detail_arr) . PHP_EOL);
                $detail_arr = array_map(function () {
                    return '';
                }, $detail_arr);
            }
            $i++;
        }
        fclose($fp);
        fclose($detail_fp);
        $excel_head = array_map(function () {
            return '';
        }, $detail_arr);
        var_dump($excel_head);
        var_dump('ql_1 wan cheng');
    }

    function excel_1()
    {
        $arr = [
            '基金名称' => '--',
            '基金编号' => '--',
            '成立时间' => '--',
            '备案时间' => '--',
            '基金备案阶段' => '--',
            '基金类型' => '--',
            '币种' => '--',
            '基金管理人名称' => '--',
            '管理类型' => '--',
            '托管人名称' => '--',
            '运作状态' => '--',
            '基金信息最后更新时间' => '--',
            '基金业协会特别提示（针对基金）' => '--',
        ];
        $read_fp = fopen('simuprodetail.csv', 'r');
        $fp = fopen('simu.csv', 'w+');
        $csvHeader = implode(',', array_keys($arr)) . PHP_EOL;
        fwrite($fp, $csvHeader);
        $i = 1;
        while (feof($read_fp) === false) {
            $row = fgets($read_fp);
            if (strlen($row) < 10) {
                continue;
            }
            echo $i . PHP_EOL;
            $row = str_replace('|||', ',', $row);
            fwrite($fp, $row . PHP_EOL);
            $i++;
        }
        fclose($read_fp);
        fclose($fp);
    }

    function msecdate($time): string
    {
        $a = substr($time, 0, 10);
        $b = substr($time, 10);
        return date('Y-m-d H:i:s', $a) . '.' . $b;
    }

    function writeErr(\Throwable $e): void
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content);
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
//CreateMysqlPoolForProjectDb::getInstance()->createMysql();
//CreateMysqlPoolForEntDb::getInstance()->createMysql();
//CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
//CreateMysqlOrm::getInstance()->createMysqlOrm();
//CreateMysqlOrm::getInstance()->createEntDbOrm();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new QueryList1($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
