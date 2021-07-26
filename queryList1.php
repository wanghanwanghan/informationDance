<?php

use App\HttpController\Models\EntDb\EntDbBasic;
use App\HttpController\Models\EntDb\EntDbInv;
use App\HttpController\Models\EntDb\EntDbModify;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\Zip\ZipService;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;
use App\HttpController\Service\HttpClient\CoHttpClient;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class QueryList1 extends AbstractProcess
{
    protected function run($arg)
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
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new QueryList1($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
