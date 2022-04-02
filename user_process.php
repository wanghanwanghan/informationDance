<?php

date_default_timezone_set('Asia/Shanghai');

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;

require_once './vendor/autoload.php';
require_once './bootstrap.php';

Core::getInstance()->initialize();

class user_process extends AbstractProcess
{
    public $pri_test = <<<Eof
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA767Gc8oWD9ckvkt6rHRg+AC8yESbAgwfLc+lWh4Izs/rvxqA
db8/hAcpO1h+6tBVzNc+3nitxN53etJyRs2Bjf0nlh74AguaNk1S/kkdzOsGdLDr
5stC/5YOXjhNB2FjzZi5r/0tk5Y/vmsRZIBCTbbwqTvc6WhPNZzDZYUhFyr3oZsR
bf3tRqfWUdTMQdX+TTOjJheVMSzK4375m76qPa36hMaM0ha/cbFdMtWj3WxkaCPm
blfwTdkxOlA7EegL6dW3UH5bRXKWUPGMev0J+LB2fe2e4gp1HoGDJQkhQac04alY
PUfxgNf3pzEJgXOW/qRgKCffK7vgmLiXBGAASQIDAQABAoIBAQDS5KTngxwwaeyB
qCZTkb802GlDieIeRg41H+ztQ1oapyZWq3n2oQXBJQ/pkO9zq0aji41c8TBs9haJ
MpysoexpxCNN2wf7vLu/JgBtkGYxRWcTzAx1scnM2/reeomEgfPwFn7kVFAC+YQz
B0Bxfs1YViyhq/OwSEDR+pKPRDmeiFL4zrPa27JO9TX6lLnwAEB5zgsk/DbpIon9
PO7BejPQ0Dth2K12YmDUGoSNFhgshLGSoYPF+BPAVAYpPOnwmbEq35X85000xi56
XZbU9NoiFABtp8Ft9YCxX+9yBjnraCH5c2SDkT1T6/wBLA8JvTRQtE2SYAPXjs3h
4xug4+CBAoGBAP35J2Gqg49iZum6KH4lOIQLzvjVl0fXy9ZciRI4y8VUvTZHurUT
Tr/ka6HbGfA6ujUO+XOm16Gu5eildbF7FOgM+SvRYspRvzfm8hLZjfhjD/XzZ4qH
As8KApioavelExcX9QLpLAcs5OOlSQFT5wP5Syeg+lYNdo2qfbgI4IyxAoGBAPGY
bU8A7RuPIk9knKvsNOr6FI0tpmgBosBh8MIyD9sScvF3kjEmltkruSCoJE6T9SvS
a3G/SNMFUx6vczIM05pSdXLTDBcBOdRL3yRPpcaDkjj/J9/UV5h7jM2l/YkA3p8n
pm3dkFy31wDlHcnCp8P+IbadJWJEJk284Zm/2zMZAoGBAJzwxAc0SUvncNTptnAN
LBlc+q8FvhAlJ871K7bY5gKw1KOgO539qmImEuTX8fVjNQHomPmAlitRWr0i0dG0
zzx+F9Od9kAzt8ghrGE9kt90x74ihU8zEudBtk0DdeZGWb+hjEQaNpzQfzi1QKHT
aSQpfumkLk3Sz/nG6x04TxphAoGAPrwwFkXNTEy8whUEQfiSPTo3P/nMrlFOa9qC
5EqPp3mA84bzJWQ546bg6cP/uY/eKET5tY3QYUuOq/cvWJ3QDNDAwtJe31JoK+KP
zSQJjiT4QWiweATxwhzDEVu7HGpnZLitFPZl2E28vPTB6XRskA5bvsnLvVqo/6K+
imgxiXECgYBrnmW0i8IRYD9AlQdxJDreBfvrsiW4RdbH5s3Ar/5nmNQpz9TLusgj
FeUxasdoXjSBuMb3Zc/aJQFfZa5Ql64QbMtM1q9v83G1iysPXP+bXqg3Wr/Ea9Jk
ezbvJLTt45TL9P3xd3x2cYNDZfWxga68E73QFjU8T5KdJiw0GNUNnA==
-----END RSA PRIVATE KEY-----
Eof;
    public $pri_dev = <<<Eof
-----BEGIN RSA PRIVATE KEY-----
MIIEowIBAAKCAQEAsbRh82NVzprm+VwaTAi+0amEU5e4NIcbrYkyhWP+VVxuJ9Ut
QCUzDMAOV02nBuiSqhwbfYSZ6QzvhguJMVg2n9xsnJpUYgcTcTuMWHMy14C9qSm9
qlgB3cgotk0Q7nJs6fO+9cq9XFA7doJgB4h6HJQUFs0t06UovNpkoZPrhDvXgwQF
jwyPgPGQ1vTae+MTLTShYlLYePEJSmjZTyqePuerwYoN9wAKNOjg5e4U6AWfpq/P
+FqW89kFVNiY2im05Rr+8/n++ZEimK72VXpNQA44ge1pJ3CFKPjWnoj0vvJYuVhQ
AUJ1fbCSh9f2z+VmRadmUMs612yXlpazvggA0wIDAQABAoIBAA6+FUY43nyGc1UK
pA/cxd/k/VpmAt0wvEYYVL2mPwpb4bOiRt/Edki/gjER+yJilxBPxqQSJSalcRWg
zV/vnpCCm+weDZQYXC+PriQEYppoTtPC575DENySZ3ZATIBLs+dw1k3T5QPMkDJT
vJ3DX8YRLt15ZizhzdBlGeYhvG862FAauTRC5z2uOFRBsaP8vvmz6qNv0oYA92yA
TZD3y3abcIr5JTHF12PZANHpOxfyBt7H9LQg96jZDiAIHxjlgqvVH0Ene0EOyuqv
oHDMEQy6Mtjm7IyTqOGogUWDsY2l4tLtPE7/44PCoJl0h1bL8qvnpujY9B8hxuWN
R/GCfCkCgYEA2QBU5ECemSA6r5SM+NuZOkNZJgjlhCx2u3CgAarT1XzeJPqVuawo
1svUdRNbUf/5Yw9kC/XUoOk4/xcZnVA08o9mO3eC8+eDAXWAl74Uc+xo7SHNDd2p
Hoi2bEGTJD462ove28ZOuINpjdcm4+cs7OuClnfwlebV65WT3cw8zM8CgYEA0aQc
0rj6NFBar/x72aMATXlsiLRrlspoD+mGEqxrq2e7sDE9TYQYE3RckhtFEjxrBuxi
6Jc7h6725aeIsA+l9aQcrPd2gY67x4M1YREinnr/Mwclm91tTlzEy+wBZOXhnI5f
Q4iV68vkZ+TbCT3KD8uCPKLyzN0ZqRG3wl35dL0CgYBBohYLC3hsvBDD9lxFELZh
pukZ1esFdSVcQA5FMtPraF8QNDKA/A9GGFRkLLycKp3VVlxeObZcDO5OSUBYEmBR
VQoIxnb3Kni3QkDopHcvMLvzrRuGLBrwv0zdpV/JwICwhUmck3hP2n9chUyf9dXi
usC+nfxIeo8NOCqHFTT2hQKBgQC6V0Kbd3pGt8n0NdusVru1IaH8XUporR8UTcEz
pfjKUZk+AnZ4CVsRJ9QEtqKNlBCaBdHg5lQuxbGF7oWL4Uzl6+rlP80hWcrFi3YO
Apof/joKlGa0hXxcNA9lJzESC1efvdklgSmpfwFV69FaBIcvxPfNiBWDTWA6rJoG
9Vr/jQKBgEchMe6EeQ+1yij07I06jGK2ys4kdGN2UVKbe4eI7GdDCWJDEIvZuSMx
pgpSamOKF9QBFsaRT/0PRKW4Ba54wTuY6Qq1OKxZjN0sRT3+YmIOKelC5dQ4Fqwx
Siv2lj6Pvq8P0a0M9Egl/7g1vMBvRG6sbVJuXaiW9Wdxug1fr6kX
-----END RSA PRIVATE KEY-----
Eof;
    public $pub = <<<Eof
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA767Gc8oWD9ckvkt6rHRg
+AC8yESbAgwfLc+lWh4Izs/rvxqAdb8/hAcpO1h+6tBVzNc+3nitxN53etJyRs2B
jf0nlh74AguaNk1S/kkdzOsGdLDr5stC/5YOXjhNB2FjzZi5r/0tk5Y/vmsRZIBC
TbbwqTvc6WhPNZzDZYUhFyr3oZsRbf3tRqfWUdTMQdX+TTOjJheVMSzK4375m76q
Pa36hMaM0ha/cbFdMtWj3WxkaCPmblfwTdkxOlA7EegL6dW3UH5bRXKWUPGMev0J
+LB2fe2e4gp1HoGDJQkhQac04alYPUfxgNf3pzEJgXOW/qRgKCffK7vgmLiXBGAA
SQIDAQAB
-----END PUBLIC KEY-----
Eof;

    protected function run_bk($arg)
    {
        $date = \Carbon\Carbon::now()->format('Ymd');
        $timestamp = \Carbon\Carbon::now()->format('YmdHis');
        $timestamp_ = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $app_id = '5dc387b32c07871d371334e9c45120ba';
        $busiMerno = '898441650210002';
        $url = 'https://testapi.gnete.com:9083/routejson';
        $method = 'gnete.upbc.code.trade.create';
        $v = '1.0.1';
        $sign_alg = '1';

        $biz_content = [
            'sndDt' => $timestamp,
            'busiMerNo' => $busiMerno,
            'notifyUrl' => '',
            'msgBody' => [
                'busiId' => '00270002',
                'vehicleVerifyInf' => [
                    'certNo' => '130223197701163416',
                    'certType' => '0',
                    'userNo' => '888888',
                    'name' => '',
                    'vin' => '',
                ],
            ],
            'merOrdrNo' => $busiMerno . $date . substr(time(), -8),
            'remark4' => '05200007',
            'remark' => '',
            'remark1' => '',
            'remark3' => '',
            'bizFunc' => '721001',
            'remark2' => '',
        ];

        $sign_arr = [
            'app_id' => $app_id,
            'timestamp' => $timestamp_,
            'v' => $v,
            'sign_alg' => $sign_alg,
            'method' => $method,
            'biz_content' => $biz_content,
        ];

        //sign sha256 with rsa
        $pkeyid = openssl_pkey_get_private($this->pri_test);
        openssl_sign(jsonEncode($sign_arr, false), $signature, $pkeyid, OPENSSL_ALGO_SHA256);

        $signature = $this->getBytes($signature);

        $signature = $this->encodeHex($signature);

        $sign = implode($signature);

        $post_arr = array_merge($sign_arr, ['sign' => $sign]);


        dd(jsonEncode($post_arr, false));


        $res = (new \App\HttpController\Service\HttpClient\CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)
            ->send($url, jsonEncode($post_arr, false), [], ['enableSSL' => true], 'postjson');

        dd($res);

    }

    protected function run($arg)
    {
        $date = \Carbon\Carbon::now()->format('Ymd');
        $timestamp = \Carbon\Carbon::now()->format('YmdHis');
        $timestamp_ = \Carbon\Carbon::now()->format('Y-m-d H:i:s');

        $app_id = '5dc387b32c07871d371334e9c45120ba';
        $busiMerno = '898441650210002';
        $url = 'https://testapi.gnete.com:9083/routejson';
        $method = 'gnete.upbc.code.trade.create';
        $v = '1.0.1';
        $sign_alg = '1';

        $biz_content = [
            'sndDt' => $timestamp,
            'busiMerNo' => $busiMerno,
            'notifyUrl' => '',
            'msgBody' => [
                'busiId' => '00270002',
                'vehicleVerifyInf' => [
                    'certNo' => '130223197701163416',
                    'certType' => '0',
                    'userNo' => '888888',
                    'name' => '',
                    'vin' => '',
                ],
            ],
            'merOrdrNo' => $busiMerno . $date . substr(time(), -8),
            'remark4' => '05200007',
            'remark' => '',
            'remark1' => '',
            'remark3' => '',
            'bizFunc' => '721001',
            'remark2' => '',
        ];

        $req_content = jsonEncode($biz_content, false);

        $sign_arr = [
            'app_id' => $app_id,
            'timestamp' => $timestamp_,
            'v' => $v,
            'sign_alg' => $sign_alg,
            'method' => $method,
            'biz_content' => $req_content,
        ];

        $sign_data = http_build_query($sign_arr);

        $sign_data = '';
        foreach ($sign_arr as $key => $val) {
            $sign_data .= $key . '=' . $val . '&';
        }
        $sign_data = rtrim($sign_data, '&');

        //sign sha256 with rsa
        $pkeyid = openssl_pkey_get_private($this->pri_test);

        openssl_sign(jsonEncode($sign_data, false), $signature, $pkeyid, OPENSSL_ALGO_SHA256);

        $signature = $this->getBytes($signature);

        $signature = $this->encodeHex($signature);

        $sign = implode($signature);

        $post_arr = array_merge($sign_arr, ['sign' => $sign]);

        $post_data = http_build_query($post_arr);

        $post_data = '';
        foreach ($post_arr as $key => $val) {
            $post_data .= $key . '=' . $val . '&';
        }
        $post_data = rtrim($post_data, '&');

        $res = (new \App\HttpController\Service\HttpClient\CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)
            ->send($url, jsonEncode($post_data, false), [], ['enableSSL' => true], 'postjson');

        dd($res);
    }

    function getBytes($str): array
    {
        $len = strlen($str);
        $bytes = [];
        for ($i = 0; $i < $len; $i++) {
            if (ord($str[$i]) >= 128) {
                $byte = ord($str[$i]) - 256;
            } else {
                $byte = ord($str[$i]);
            }
            $bytes[] = $byte;
        }
        return $bytes;
    }

    function encodeHex($data): array
    {
        $toDigits = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f'];
        $len = count($data);
        $i = 0;
        $out = array();
        for ($var = 0; $i < $len; ++$i) {
            $var1 = 240 & $data[$i];
            $index1 = $this->unsignedRight($var1, 4);
            $out[$var] = $toDigits[$index1];
            $var++;
            $index2 = 15 & $data[$i];
            $out[$var] = $toDigits[$index2];
            $var++;
        }
        return $out;
    }

    function unsignedRight($int, $n): int
    {
        for ($i = 0; $i < $n; $i++) {
            if ($int < 0) {
                $int >>= 1;
                $int &= PHP_INT_MAX;
            } else {
                $int >>= 1;
            }
        }
        return $int;
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
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

for ($i = 1; $i--;) {
    $conf = new Config();
    $conf->setArg(['foo' => $i]);
    $conf->setEnableCoroutine(true);
    $process = new user_process($conf);
    $process->getProcess()->start();
}

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
