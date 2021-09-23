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
    public $pub_str = <<<Eof
-----BEGIN PUBLIC KEY-----
MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuppDpYsLE7EkHXVZhNCe
FtqMvfxDX4FmYxTZNT/twHltVbgEn4lQQ0FTeKYRZk0oplGKGGgcQWQT5NYT4pfX
AfKbUqD78Nlot28Tsq+H/BVCr7joaIHQCQbe+CQ3R8G7ZMEELYGEPIXdjb2g8HRe
Oo9wZjG0VAH6hOeAguoH+DHyY54aCPwStF8AVv9e2EWJFqTLgx37tXMhH+CXbchw
LXgsOcf/CBNsXYfM7AgkuWXF4jEyjDP9p1v20BeCn1UrJ1P8laUZNvv+sJeti2x0
BtalB89NfQ72VL0SwS310QGxQ0ssZfoXrndYQMtq0TaR0g1uz4ibb/N8aw0cwmkP
TQIDAQAB
-----END PUBLIC KEY-----
Eof;
    public $pri_str = <<<Rof
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
Rof;


    public $currentAesKey = 'XbrAdtkxFflSaIoE';
    public $iv = '1234567890abcdef';
    public $oss_expire_time = 86400 * 7;
    public $oss_bucket = 'invoice-mrxd';

    protected function run($arg)
    {
        //$this->sendToOSS('91330108MA2KE69H8J');
        $url = 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify';

        $fileSecret = control::rsaEncrypt($this->currentAesKey, $this->pub_str, 'pub');

        $body = [
            'nsrsbh' => '91330108MA2KE69H8J',//授权的企业税号
            'authResultCode' => '0000',//取数结果状态码 0000取数成功 XXXX取数失败
            'fileSecret' => $fileSecret,//对称钥秘⽂
            'companyName' => '杭州随便文化传媒有限公司',//公司名称
            'authTime' => '2021-10-22 12:34:45',//授权时间
            'fileKeyList' => [
                'http://invoice-mrxd.oss-cn-beijing.aliyuncs.com/202109_91330108MA2KE69H8J.zip?OSSAccessKeyId=LTAI4GFmzB3tJgMTpcM35EPP&Expires=1632971608&Signature=ukAxuRR9TH7OzGEyA6uCrXv7QXE%3D'
            ],//文件路径
        ];
        //sign md5 with rsa
        $pkeyid = openssl_pkey_get_private($this->pri_str);
        $verify = openssl_sign(jsonEncode([$body], false), $signature, $pkeyid, OPENSSL_ALGO_MD5);
        //准备通知
        $collectNotify = [
            'body' => [$body],
            'head' => [
                'sign' => base64_encode($signature),//签名
                'notifyChannel' => 'ELEPHANT',//通知 渠道
            ],
        ];

        $ret = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $collectNotify);

        echo jsonEncode([$body], false) . PHP_EOL;
        echo jsonEncode($collectNotify, false) . PHP_EOL;


    }

    //上传到oss 发票已经入完mysql
    function sendToOSS($NSRSBH)
    {
        //只有蚂蚁的税号才上传oss
        //蚂蚁区块链dev id 36
        //蚂蚁区块链pre id 41
        //蚂蚁区块链pro id 42

        $info = AntAuthList::create()
            ->where('belong', [36, 41, 42], 'IN')
            ->where('socialCredit', $NSRSBH)
            ->get();

        if (empty($info)) return false;

        $store = MYJF_PATH . $NSRSBH . DIRECTORY_SEPARATOR . Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR;
        is_dir($store) || mkdir($store, 0755, true);

        //取全部发票写入文件
        $total = EntInvoice::create()
            ->addSuffix('911199999999CN0008', 'wusuowei')
            ->where('nsrsbh', $NSRSBH)
            ->count();

        if (empty($total)) {
            $filename = $NSRSBH . "_page_1.json";
            file_put_contents($store . $filename, '', FILE_APPEND | LOCK_EX);
        } else {
            $totalPage = $total / 3000 + 1;
            //每个文件存3000张发票
            for ($page = 1; $page <= $totalPage; $page++) {
                //每个文件存3000张发票
                $filename = $NSRSBH . "_page_{$page}.json";
                $offset = ($page - 1) * 3000;
                $list = EntInvoice::create()
                    ->addSuffix('911199999999CN0008', 'wusuowei')
                    ->where('nsrsbh', $NSRSBH)
                    ->field([
                        'fpdm',
                        'fphm',
                        'kplx',
                        'xfsh',
                        'xfmc',
                        'xfdzdh',
                        'xfyhzh',
                        'gfsh',
                        'gfmc',
                        'gfdzdh',
                        'gfyhzh',
                        'gmflx',
                        'kpr',
                        'skr',
                        'fhr',
                        'yfpdm',
                        'yfphm',
                        'je',
                        'se',
                        'jshj',
                        'bz',
                        'zfbz',
                        'zfsj',
                        'kprq',
                        'fplx',
                        'fpztDm',
                        'slbz',
                        'rzdklBdjgDm',
                        'rzdklBdrq',
                        'direction',
                        'nsrsbh',
                    ])
                    ->limit($offset, 3000)
                    ->all();
                //没有数据了
                if (empty($list)) break;
                foreach ($list as $oneInv) {
                    //每张添加明细
                    $detail = EntInvoiceDetail::create()
                        ->addSuffix($oneInv->getAttr('fpdm'), $oneInv->getAttr('fphm'), 'wusuowei')
                        ->where(['fpdm' => $oneInv->getAttr('fpdm') - 0, 'fphm' => $oneInv->getAttr('fphm') - 0])
                        ->field([
                            'spbm',
                            'mc',
                            'jldw',
                            'shul',
                            'je',
                            'sl',
                            'se',
                            'mxxh',
                            'dj',
                            'ggxh',
                        ])
                        ->all();
                    empty($detail) ? $oneInv->fpxxMxs = null : $oneInv->fpxxMxs = $detail;
                }
                $content = jsonEncode($list, false);
                //AES-128-CTR
                $content = base64_encode(openssl_encrypt($content, 'AES-128-CTR', $this->currentAesKey, OPENSSL_RAW_DATA, $this->iv));
                file_put_contents($store . $filename, $content . PHP_EOL, FILE_APPEND | LOCK_EX);
            }
        }

        //上传oss
        $file_arr = [];

        if ($dh = opendir($store)) {
            $ignore = [
                '.', '..', '.gitignore',
            ];
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    $file_arr[] = $store . $file;
                }
            }
        }
        closedir($dh);

        if (!empty($file_arr)) {
            $name = Carbon::now()->format('Ym') . "_{$NSRSBH}.zip";
            if (file_exists($store . $name)) {
                unlink($store . $name);
            }
            $zip_file_name = ZipService::getInstance()->zip($file_arr, $store . $name, true);
            $oss_file_name = OSSService::getInstance()
                ->doUploadFile($this->oss_bucket, $name, $zip_file_name, $this->oss_expire_time);
            CommonService::getInstance()->log4PHP($oss_file_name);
            //更新上次取数时间和oss地址
            AntAuthList::create()
                ->where('socialCredit', $NSRSBH)
                ->update([
                    'lastReqTime' => time(),
                    'lastReqUrl' => $oss_file_name,
                ]);
        }

        return true;
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
