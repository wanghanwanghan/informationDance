<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\EntDb\EntInvoice;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\MaYi\MaYiService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class GetInvData extends AbstractCronTask
{
    public $crontabBase;
    public $currentAesKey;
    public $redisKey = 'readyToGetInvData_';
    public $readToSendAntFlag = 'readyToGetInvData_readToSendAntFlag_';

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每月19号凌晨4点可以取上一个月全部数据
        //return '0 4 19 * *';
        return '57 16 30 * * ';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $this->currentAesKey = getRandomStr();

        $redis = Redis::defer('redis');
        $redis->select(15);

        $redis->hset($this->readToSendAntFlag, 'current_aes_key', $this->currentAesKey);

        for ($i = 1; $i <= 99999999; $i++) {
            $limit = 1000;
            $offset = ($i - 1) * $limit;
            $list = AntAuthList::create()
                ->where('status', MaYiService::STATUS_3)
                ->limit($offset, $limit)->all();
            if (empty($list)) {
                break;
            }
            //可以取数了
            foreach ($list as $one) {
                $id = $one->getAttr('id');
                $suffix = $id % \App\Process\ProcessList\GetInvData::ProcessNum;
                //放到redis队列
                $key = $this->redisKey . $suffix;
                $redis->lPush($key, jsonEncode($one, false));
                $redis->hIncrBy($this->readToSendAntFlag, $this->readToSendAntFlag . $suffix, 1);
            }
        }

        //判断 $this->readToSendAntFlag 里是不是都是0，0代表没有处理的任务
        while (true) {
            $flag_arr = [];
            $num = \App\Process\ProcessList\GetInvData::ProcessNum;
            for ($i = $num; $i--;) {
                $flag = $redis->hGet($this->readToSendAntFlag, $this->readToSendAntFlag . $i) - 0;
                $flag > 0 ?: $flag_arr[] = $flag;
            }
            if (count($flag_arr) !== $num) {
                \co::sleep(3);
                continue;
            }
            $ret = $this->sendToAnt();
            break;
        }
    }

    //通知蚂蚁
    function sendToAnt(): bool
    {
        //根据三个id，通知不同的url
        $url_arr = [
            36 => 'https://invoicecommercial.test.dl.alipaydev.com/api/wezTech/collectNotify',//dev
            //36 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',//test rsa和dev一样
            41 => 'https://invoicecommercial-pre.antfin.com/api/wezTech/collectNotify',//pre
            42 => 'https://invoicecommercial.antfin.com/api/wezTech/collectNotify',//pro
        ];

        $total = AntAuthList::create()
            ->where('belong', array_keys($url_arr), 'IN')
            ->count();

        if (empty($total)) return false;

        $totalPage = $total / 2000 + 1;

        for ($page = 1; $page <= $totalPage; $page++) {
            $offset = ($page - 1) * 2000;
            $list = AntAuthList::create()
                ->where('belong', array_keys($url_arr), 'IN')
                ->limit($offset, 2000)
                ->all();
            if (empty($list)) break;
            foreach ($list as $oneReadyToSend) {
                $lastReqTime = $oneReadyToSend->getAttr('lastReqTime');
                //拿私钥
                $id = $oneReadyToSend->getAttr('belong') - 0;
                $info = RequestUserInfo::create()->get($id);
                $rsa_pub_name = $info->getAttr('rsaPub');
                $rsa_pri_name = $info->getAttr('rsaPri');
                //5天以内的才算取数成功，上传oss后更新lastReqTime，然后才会执行这里
                if (time() - $lastReqTime < 86400 * 5) {
                    $authResultCode = '0000';
                    //拿公钥加密
                    $stream = file_get_contents(RSA_KEY_PATH . $rsa_pub_name);
                    //AES加密key用RSA加密
                    $fileSecret = control::rsaEncrypt($this->currentAesKey, $stream, 'pub');
                    $fileKeyList = empty($oneReadyToSend->getAttr('lastReqUrl')) ?
                        [] :
                        array_filter(explode(',', $oneReadyToSend->getAttr('lastReqUrl')));
                } else {
                    $authResultCode = '9999';
                    $fileSecret = '';
                    $fileKeyList = [];
                }

                //拿一下这个企业的进项销项总发票数字
                $in = EntInvoice::create()->addSuffix($oneReadyToSend->getAttr('socialCredit'), '')->where([
                    'nsrsbh' => $oneReadyToSend->getAttr('socialCredit'),
                    'direction' => '01',//01-进项
                ])->count();
                $out = EntInvoice::create()->addSuffix($oneReadyToSend->getAttr('socialCredit'), '')->where([
                    'nsrsbh' => $oneReadyToSend->getAttr('socialCredit'),
                    'direction' => '02',//02-销项
                ])->count();

                $body = [
                    'nsrsbh' => $oneReadyToSend->getAttr('socialCredit'),//授权的企业税号
                    'authResultCode' => $authResultCode,//取数结果状态码 0000取数成功 XXXX取数失败
                    'fileSecret' => $fileSecret,//对称钥秘⽂
                    'companyName' => $oneReadyToSend->getAttr('entName'),//公司名称
                    'authTime' => date('Y-m-d H:i:s', $oneReadyToSend->getAttr('requestDate')),//授权时间
                    'totalCount' => ($in + $out) . '',
                    'fileKeyList' => $fileKeyList,//文件路径
                    //'notifyType' => 'INVOICE' //通知发票
                ];
                $num = $in + $out;
                $dateM = (time() - $oneReadyToSend->getAttr('requestDate')) / 86400;
                if (empty($num) && $dateM < 30) {
                    $body['authResultCode'] = '9000';//'没准备好';
                    AntEmptyLog::create()->data([
                        'nsrsbh' => $body['nsrsbh'],
                        'data' => json_encode($body)
                    ])->save();
                }
                // authTime 和当前时间对比在一个月之内，$in + $out都是空时，返回状态：没准备好；
                // 增加，对没准备好数据的记录表，方便日后和大象对账

                ksort($body);//周平说参数升序

                //sign md5 with rsa
                $private_key = file_get_contents(RSA_KEY_PATH . $rsa_pri_name);
                $pkeyid = openssl_pkey_get_private($private_key);
                $verify = openssl_sign(jsonEncode([$body], false), $signature, $pkeyid, OPENSSL_ALGO_MD5);

                //准备通知
                $collectNotify = [
                    'body' => [$body],
                    'head' => [
                        'sign' => base64_encode($signature),//签名
                        'notifyChannel' => 'ELEPHANT',//通知 渠道
                    ],
                ];

                $url = $url_arr[$id];

                $header = [
                    'content-type' => 'application/json;charset=UTF-8',
                ];

                //生产环境先不通知
                if ($oneReadyToSend->belong - 0 === 41) {
                    CommonService::getInstance()->log4PHP([$body], 'info', 'notify_fp');

                    $ret = (new CoHttpClient())
                        ->useCache(false)
                        ->needJsonDecode(true)
                        ->send($url, jsonEncode($collectNotify, false), $header, [], 'postjson');

                    CommonService::getInstance()->log4PHP($ret, 'info', 'notify_fp');
                }

            }
        }

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
