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
MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQDBm/ZvYyFDDs9+
wCCMeMi86ig7Sv65DlglQeys5QLGfqPCwO3RyGs3gppWGM3v/cwkyvXGzGVw/1uL
vo3GyhHE4MZDk0T9kIe5Q4UILafgVP/l378/SiBDRGsxvTQ7/ocf7MSdpTeEdzzB
B1o+pXSDOYe56N+5lIwv5z7ah+OB74Z6NGDqyMI9OPAoAanAHEsnBJpVgGhSw+XB
JYO8ZzdTVs7viqgnIcoN1UiTXu19LyNBZfgkeKDP+4wG8C5+3lH/IFdhD9vEh8ns
7L5DKxy4NroGwpYyVl846VxzkxxMfvyFpUbcsNnTwTJtbbopPTKs6UoeiKok8fil
s0rMZpjPAgMBAAECggEARr9DEfjbUrG6yMpUGoCYec/m26PP6LeBJjwszBDzLq1g
Ee6F+L6Pzzz+QK/XsPbA/kDcBsTx3JSzUyFSlW2JiLPKPy81aqLBtcUie5aTXbox
uEJGlE319B6wPQCycanUnqnaPvD8lH8tyCtzoqi7JqiDHEAoYJwTjf2mThyR2gyV
vDlDZ5Sf8WbnX6zfu+stWVrWrWgibWH/rOE/dsY7bxXYbBIFvzFori0wdGJRIkXG
BYmSAnizxITprZZraatygQbTFHP45GIaTM1eYJLRzdjpDobNivu4GSIQ61948ZHO
eu0j/g3UEFLm2zrZ0ZQicmEYvR5SO/hTe0MeO1m+YQKBgQDtzFG4Zm4h9X+JANoS
O+D4toOVMolCEk3QBaIqtZ5kzpLJhG9G8quWJPwqIZF8awCnR+ffC6blaY4RMOOI
e2xryBK5485d/L8tJaVyiIhHWE2pUVQeqFfnSZ7T7wyhdmBs6VzEEsompzHHksGd
lvIp5cfGYvg6PVzowiyJOUeJgwKBgQDQbcHStiAi7lOrlUHaNmviD/OSaSVqsXg2
J/DZxkkIrjqDMiAbR8xHU6QRumvDYFt2w+tlSykmoJ2lZc4fqamc4LU7BbX+/shp
pmZu8SQyoCYNQFC0qm9R9JH2TuakYycnspCdWc8+wp0b8MK4VstE6dBIBFnBQEfN
Ri/lYW9txQKBgQDFgCATVkd8PujYwfNcl4znJLcukFV9obQs5LDmZgeS1BsH8c12
EJDAWCFzYIPz4O8fAFKtZoEMItoSnxcrQM8wyW/8Ih9A4m1pss2xzYHaN5Xw3ZJP
ECRJ/VRD01QbOjUl37/jPXPWHKD0j4ftOfQRJj4BICvoOxTSYIsawY8PvwKBgEUR
+kcvn5qzy/pybe44VqwFiTwdqA6hXSrlNYWVliJQSoerlsQzmNiSOS6+znNifSzw
ZBOfQrXQSC3FfPc1LEYWmThD/jnQiO2p/QwK0WoNdE6z34rfaCCKocwz/W7AhPs8
y3u5hVpQ9+uIb57S9G2T5jfXaT8HZEP8XGbxURHZAoGBANq2cWnhNAxaGe2+FTlK
SDTqxuV4GSUu62VS8BCcuGFw4k2fdbTdDAuXXV1DvuZWDx6rUJl81rlJQSr7OZDK
vkcuLuooqFVmUcqgmJIqw0u2saefDVVKKAnVpGEqw0WUYGE3zpuJidipgmep8USt
Whz7Hw4ShokrTY9lB2bwYXHl
-----END PRIVATE KEY-----
Eof;

    public $currentAesKey = 'DrPEsgNVA4fBmtZ3';

    protected function run($arg)
    {
        $url = 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify';

        $fileSecret = control::rsaEncrypt($this->currentAesKey, $this->pri_str, 'pri');
        $fileKeyList = [];

        $body = [
            'nsrsbh' => '91330108MA2KE69H8J',//授权的企业税号
            'authResultCode' => '0000',//取数结果状态码 0000取数成功 XXXX取数失败
            'companyName' => '杭州随便文化传媒有限公司',//公司名称
            'authTime' => 1632300556,//授权时间
            'fileSecret' => $fileSecret,//对称钥秘⽂
            'fileKeyList' => $fileKeyList,//文件路径
        ];
        //sign md5 with rsa
        $pkeyid = openssl_pkey_get_private($this->pri_str);
        $verify = openssl_sign(json_encode($body, 256), $signature, $pkeyid, OPENSSL_ALGO_MD5);
        //准备通知
        $collectNotify = [
            'body' => [$body],
            'head' => [
                'sign' => base64_encode($signature),//签名
                'notifyChannel' => 'ELEPHANT',//通知 渠道
            ],
        ];

        $ret = (new CoHttpClient())->useCache(false)->send($url, $collectNotify);


        var_dump(jsonEncode($collectNotify, false));

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
