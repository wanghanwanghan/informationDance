<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\EntDb\EntInvoiceDetail;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\OSS\OSSService;
use App\HttpController\Service\Zip\ZipService;
use Carbon\Carbon;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use EasySwoole\ORM\DbManager;
use wanghanwanghan\someUtils\control;

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
        for ($i = 1; $i <= 24; $i++) {

            $kpyf = Carbon::now()->subMonths($i)->format('Ym');

            $url = $this->baseUrl . 'FP/getFpxzStatus';

            $data = [
                'nsrsbh' => $this->nsrsbh,
                'kpyf' => $kpyf - 0,//Ym
                'jxxbzs' => ['jx', 'xx'],
                'fplxs' => ['01', '03', '04', '08', '10', '11', '14', '15', '17'],
                'addJob' => false
            ];

            $req_date = time() . mt_rand(100, 999);

            $token = $this->createToken();

            $sign = base64_encode(
                md5('POST_' . md5(jsonEncode($data, false)) . '_' . $req_date . '_' . $token . '_' . $this->secret)
            );

            $req_sign = "API-SV1:{$this->appkey}:" . $sign;

            $header = [
                'content-type' => 'application/json;charset=UTF-8',
                'access_token' => $token,
                'req_date' => $req_date,
                'req_sign' => $req_sign,
            ];

            $res = (new CoHttpClient())
                ->useCache(false)
                ->needJsonDecode(false)
                ->send($url, $data, $header, [], 'postjson');

            var_dump($res);
        }
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
