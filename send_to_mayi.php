<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3NicCode;
use App\HttpController\Service\CreateMysqlPoolForRDS3Prism1;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateMysqlPoolForRDS3SiJiFenLei;
use App\HttpController\Service\HttpClient\CoHttpClient;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class send_to_mayi extends AbstractProcess
{
    public $baseUrl = 'http://api.qixiangyun.com/v1/';
    public $appkey = '10002009';
    public $secret = 'OkdvOUZ3fxb3mNxtko69nedNPgkjY2E8gBP7x7opkoY5tSyO';
    public $nsrsbh = '91110108MA01KPGK0L';

    function createToken(): string
    {
        $url = $this->baseUrl . 'AGG/oauth2/login';

        $data = [
            'grant_type' => 'client_credentials',
            'client_appkey' => $this->appkey,
            'client_secret' => md5($this->secret),
        ];

        $header = [
            'content-type' => 'application/json;charset=UTF-8'
        ];

        $res = (new CoHttpClient())
            ->useCache(false)->setEx(0.3)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');

        return $res['value']['access_token'];
    }

    protected function run($arg)
    {
        //        $res = sqlRaw('select * from company where id = 1;', 'prism1');
        $res =\App\HttpController\Models\RDS3\Company::create()->limit(1)->get();


        dd($res);
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

//mysql pool
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlPoolForRDS3NicCode::getInstance()->createMysql();
CreateMysqlPoolForRDS3SiJiFenLei::getInstance()->createMysql();
CreateMysqlPoolForRDS3Prism1::getInstance()->createMysql();

//mysql orm
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();
CreateMysqlOrm::getInstance()->createRDS3Orm();
CreateMysqlOrm::getInstance()->createRDS3NicCodeOrm();
CreateMysqlOrm::getInstance()->createRDS3SiJiFenLeiOrm();
CreateMysqlOrm::getInstance()->createRDS3Prism1Orm();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new send_to_mayi($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
