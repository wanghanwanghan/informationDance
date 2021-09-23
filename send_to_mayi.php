<?php

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
    public $pri_str = <<<Eof
-----BEGIN PRIVATE KEY-----
MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDK1da3WBBdzfo3
RTqqwzm/R7DLRg+/i0kd+kGenkevJFm6EbD5PF+qQsKfS8/XrCAaL+A3sDjCdTZa
utw+PohkAYP/jiWR13KKTh5hXcIw3kero5J1ryLQlNrXRoVGYPg5wQ0TxF3shgu3
9weZ1VQA2jL2NnZleI2ZkeNrvvQiywr+LrfVTaG6h8oIFZo5xa9wyk2jrpnUbJpK
G/hkxP4sdzZgMRVIYMUALU1TnrPZ9jp5yEpM6h6uTQsyl2SraelBhv++McR9tIdh
cYkAx1yYMxquM4gxrCBltMHnI4CXXypuRUPED8AUUkxIoNmsLqzYq7PSoxApkDMS
1jkL+4plAgMBAAECggEAXPOL1y9rKKGo5cU68mBOyWKAGVc9Bkk9M5iok5jzPsbI
u6U51a2eJXc8myVx1OMTPwzrknmWOT7frsps/bVIaZPsOqOYgfIakljkQThnMl/a
tkRabMXajX15oCe4EZ2Eg2r9pC5b4HU5T4/MEuoY+d8EdaJVtYB+W37omkrCtGJI
XT3kJvCUzOCdBuVlTUIYxC7X26yb6gTdjyIUiwe8+d3ZJ8k8fhigxFnDBbaUz+AF
d+FfjfpjQjst6BfOsPjZdrx/v2dkTEYqY7h7lFm8W4Tc1oKncKgmvGhu5+WK79JI
5PX26wA5jTWRCCGf/MsrHAb+qvQe4uk/2oSyjuMbIQKBgQD20+mov06KrwwtWrDe
B0xjV1Wlj57R2djdoI0kld8a+QYYAEY3QQcRJEmWwbSKE0H5BPrkHzR9K31TCru9
QRsWKN+AJfPUsuMhAdX+8nW9TUJBpiJd9XDBeRJNdXluxnA2XpLsR2nn35VP+NgQ
5q7qhsR1zG8Jtl0zIK4VPAY07QKBgQDSX2xIZwcHLYhUoOTOUEL7fpiEOOGTiLl3
tBrpsDE7D4yKP5u6f36anHVOxQt2OUJN9AYUaexYGx0dn1/29EWmK4kBDIHtIri5
1R60EDvqCYJQiMHvatX7xwaD0YMtG1Qgavscnum1JK2W2oO/asko4OZ6TC2d1c/M
y674n/80WQKBgDLxLapsQXOSCTtbGmHYs8VVAxI2gBrjkUS8nCTO4csZVk6hz9wb
ia/aA24f8HkG3HjetEFcx2KGFUmMT48R3ttF+Erkilx9xy7KyDXkKLS3O1N9TF6E
B4+Gw2ZFNpjMT+CIyF4Hpy36EUD+JOnoEnXI9scxOEGS581jk0pCpy1JAoGADDu2
TUOIehdgvSMaCxFJw9wpvE6ed3jU6CwWAI7ZXgjacFOgl6jAUPdWLv1wXDCaNXRC
Qj+imcEB4W4aI38y6aXQcroqeAKz4UKOZYQoJ8TjyhEZzfVVei2pqFKvoRjcvIHc
Fl77UihO293bGW95QSJK5MO3R11elxclFpofOgkCgYAX/v9xC1+1XKxVuo5knxxK
13klfa0OMlxnkM6xhZOHr+xIR5U/DK2eiKRGLIBb+mSGN2QctHNafvf61F66GyTq
wrw2lJObnDXs2bq+4i+Yvql+AsZWiof6JRR+IOtGOX3OH7haAFpaFJCpVJB+W8Tl
/h/5usUNiODhF7ct+AYitg==
-----END PRIVATE KEY-----
Eof;

    public $currentAesKey = 'DrPEsgNVA4fBmtZ3';

    protected function run($arg)
    {
        $url = 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify';

        $fileSecret = control::rsaEncrypt($this->currentAesKey, $this->pri_str, 'pri');

        $body = [
            'nsrsbh' => '91330108MA2KE69H8J',//授权的企业税号
            'authResultCode' => '0000',//取数结果状态码 0000取数成功 XXXX取数失败
            'fileSecret' => $fileSecret,//对称钥秘⽂
            'companyName' => '杭州随便文化传媒有限公司',//公司名称
            'authTime' => '2021-09-22 12:34:45',//授权时间
            'fileKeyList' => [],//文件路径
        ];
        //sign md5 with rsa
        $pkeyid = openssl_pkey_get_private($this->pri_str);
        $verify = openssl_sign(json_encode([$body], 320), $signature, $pkeyid, OPENSSL_ALGO_MD5);
        //准备通知
        $collectNotify = [
            'body' => [$body],
            'head' => [
                'sign' => base64_encode($signature),//签名
                'notifyChannel' => 'ELEPHANT',//通知 渠道
            ],
        ];

        $ret = (new CoHttpClient())->useCache(false)->send($url, $collectNotify);

        echo json_encode([$body], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;

        echo json_encode($collectNotify, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;


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
