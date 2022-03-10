<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntAuthSealDetail;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaDaDa\FaDaDaService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\HuiCheJian\HuiCheJianService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\OSS\OSSService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class GetAuthBook extends AbstractCronTask
{
    private $crontabBase;
    public $currentAesKey;
    public $iv = '1234567890abcdef';
    public $oss_bucket = 'invoice-mrxd';
    public $oss_expire_time = 86400 * 60;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每分钟执行一次
        return '41 17 10 * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $this->currentAesKey = getRandomStr();
        //根据三个id，通知不同的url
        $url_arr = [//http://invoicecommercial.test.dl.alipaydev.com
            36 => 'https://invoicecommercial.test.dl.alipaydev.com/api/wezTech/collectNotify',//dev
//            36 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',//http://invoicecommercial.test.dl.alipaydev.com/api/wezTech/collectNotify',//dev
            41 => 'https://invoicecommercial-pre.antfin.com/api/wezTech/collectNotify',//pre
            42 => 'https://invoicecommercial.antfin.com/api/wezTech/collectNotify',//pro
        ];
        $ids = $this->getNeedSealID();
        //准备获取授权书的企业列表
//        $list = AntAuthList::create()->where([
//            'authDate' => 0,
//            'status' => MaYiService::STATUS_0,
//        ])->all();
        $list = sqlRaw("select * from information_dance_ant_auth_list where id in(" . implode(',', $ids) . ") or (authDate = 0 and status = '" . MaYiService::STATUS_0 . "')", CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $url = [];
        $fileData = [];
//        $fileIdS = [];
        $flieDetail = [];
        CommonService::getInstance()->log4PHP($list,'info','get_auth_file_list');

        if (!empty($list)) {
            foreach ($list as $oneEntInfo) {
                CommonService::getInstance()->log4PHP($oneEntInfo,'info','get_auth_file_list_oneEntInfo');

                $data = [
                    'entName' => $oneEntInfo['entName'],// entName companyname
                    'socialCredit' => $oneEntInfo['socialCredit'],//taxno  newtaxno
                    'legalPerson' => $oneEntInfo['legalPerson'],//signName
                    'idCard' => $oneEntInfo['idCard'],
                    'phone' => $oneEntInfo['phone'],//phoneno
                    'city' => $oneEntInfo['city'],//region
                    'regAddress' => $oneEntInfo['regAddress'],//address
//                    'requestId' => $oneEntInfo->getAttr('requestId') . time(),//海光用的，没啥用，随便传
                ];
//                CommonService::getInstance()->log4PHP($data,'info','get_auth_file_list_data');
                $DetailList = AntAuthSealDetail::create()->where([
                    'antAuthId' => $oneEntInfo['id'],
                ])->all();
                CommonService::getInstance()->log4PHP($DetailList,'info','get_auth_file_list_DetailList');

                if (empty($DetailList)) {
                    $url['2'] = $this->getDataSealUrl($data);
                } else {
                    $notNoodIsSeal = false;
                    foreach ($DetailList as $value) {
                        if ($value->getAttr('isSeal')) {
                            if ($value->getAttr('type') != 2) {
                                $url[$value->getAttr('type')] = $this->getSealUrl($data, $value->getAttr('fileAddress'));
                            } else {//数字代办委托书盖章加填充
                                $url['2'] = $this->getDataSealUrl($data);
                            }
                        } else {
                            $notNoodIsSeal = true;
                        }
                        $fileData[$value->getAttr('type')] = [
                            'fileAddress' => '',
                            'type' => $value->getAttr('type'),
                            'isSealed' => (boolean)$value->getAttr('isSealed'),
                            'fileName' => '',
                        ];
                        $flieDetail[$value->getAttr('type')]['fileId'] = $value->getAttr('fileId');
                    }
                    CommonService::getInstance()->log4PHP($url, 'info', 'get_auth_file_list_urlArr');
                    //如果不需要盖章，就跳过
                    if ($notNoodIsSeal) {
                        continue;
                    }
                    foreach ($url as $type => $v) {
                        AntAuthSealDetail::create()->where([
                            'type' => $type,
                            'antAuthId' => $oneEntInfo['id'],
                        ])->update([
                            'fileUrl' => $v,
                            'status' => empty($v) ? 2 : 1
                        ]);
                        CommonService::getInstance()->log4PHP($fileData, 'info', 'get_auth_file_list_url_v');
                        list($file_url, $fileName) = $this->getOssUrl($v, $data['socialCredit'], $flieDetail[$type]);
                        CommonService::getInstance()->log4PHP($fileData, 'info', 'get_auth_file_list_file_url');

                        $fileData[$type]['fileAddress'] = $file_url;
                        $fileData[$type]['fileName'] = $fileName;
                        ksort($fileData[$type]);
                    }
                }
                CommonService::getInstance()->log4PHP($fileData, 'info', 'get_auth_file_list_fileData');

                //更新数据库
                AntAuthList::create()->where([
                    'entName' => $oneEntInfo['entName'],
                    'socialCredit' => $oneEntInfo['socialCredit'],
                    'status' => MaYiService::STATUS_0
                ])->update([
                    'filePath' => $url['2'],
                    'authDate' => time(),
                    'status' => MaYiService::STATUS_1
                ]);

                //蚂蚁没有传需要盖章的文件过来时，就不需要通知蚂蚁
                if (empty($DetailList)) continue;

                $id = $oneEntInfo['belong'] - 0;
                $info = RequestUserInfo::create()->get($id);
                $rsa_pub_name = $info->getAttr('rsaPub');
                $rsa_pri_name = $info->getAttr('rsaPri');
                $authResultCode = '0000';

                //拿公钥加密
                $stream = file_get_contents(RSA_KEY_PATH . $rsa_pub_name);
                //AES加密key用RSA加密
                $fileSecret = control::rsaEncrypt($this->currentAesKey, $stream, 'pub');

                $body = [
                    'sealResultCode' => $authResultCode,
                    'orderNo' => $oneEntInfo['orderNo'] . '',
                    'nsrsbh' => $oneEntInfo['socialCredit'],//授权的企业税号
                    'notifyType' => 'AGREEMENT', //通知类型
                    'fileData' => array_values($fileData),
                    'fileSecret' => $fileSecret,//对称钥秘⽂
                ];
                ksort($body);//周平说参数升序

                //sign md5 with rsa
                $private_key = file_get_contents(RSA_KEY_PATH . $rsa_pri_name);
                $pkeyid = openssl_pkey_get_private($private_key);
                openssl_sign(jsonEncode([$body], false), $signature, $pkeyid, OPENSSL_ALGO_MD5);

                //准备通知
                $collectNotify = [
                    'body' => [$body],
                    'head' => [
                        'sign' => base64_encode($signature),//签名
                        'notifyChannel' => 'ELEPHANT',//通知 渠道
                    ],
                ];
                $url = $url_arr[$id];
                $this->sendAnt($url, $collectNotify);
            }

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

    public function sendAnt($url, $collectNotify)
    {
        $header = [
            'content-type' => 'application/json;charset=UTF-8',
        ];

        CommonService::getInstance()->log4PHP([
            '发给蚂蚁的',
            $collectNotify
        ], 'info', 'ant.log');

        $ret = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, jsonEncode($collectNotify, false), $header, [], 'postjson');

        CommonService::getInstance()->log4PHP([
            '蚂蚁返回的',
            $ret
        ]);
    }

    /*
     * 多个文件盖章，是否是只有企业授权书需要去判断是否需要盖章，确定下一个企业是否一定只会有一个是需要盖章的
     */
    public function getSealUrl($data, $file_address)
    {
        $data['file_address'] = $file_address;
        $res = (new FaDaDaService())->setCheckRespFlag(true)->getAuthFileForAnt($data);
        CommonService::getInstance()->log4PHP($res, 'info', 'get_auth_file_return_res');
        if ($res['code'] !== 200) {
            $message = ['name' => '异常内容', 'msg' => json_encode($res)];
            dingAlarmMarkdownForWork('法大大授权书接口异常', $message);
            return '';
        }
        return $res['result']['url'];
    }

    /*
     * 获取需要填充数据和盖章的授权书
     */
    public function getDataSealUrl($data)
    {
        $res = (new FaDaDaService())->setCheckRespFlag(true)->getAuthFile($data);
        CommonService::getInstance()->log4PHP($res, 'info', 'get_auth_file_return_res');
        if ($res['code'] !== 200) {
            $message = ['name' => '异常内容', 'msg' => json_encode($res)];
            dingAlarmMarkdownForWork('法大大授权书接口异常', $message);
            return '';
        }
        return $res['result']['url'];
    }

    public function getOssUrl($path, $socialCredit, $flieDetail)
    {
        if (empty($path)) return '';

        $fileName = $socialCredit . '_' . $flieDetail['fileId'] . '_' . control::getUuid(8);

        $content = file_get_contents(INV_AUTH_PATH . $path);

        $content = base64_encode(openssl_encrypt(
            $content,
            'AES-128-CTR',
            $this->currentAesKey,
            OPENSSL_RAW_DATA,
            $this->iv
        ));

        file_put_contents(INV_AUTH_PATH . $path . '.aes', $content . PHP_EOL);

        return [OSSService::getInstance()
            ->doUploadFile(
                $this->oss_bucket,
                Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR . $fileName,
                INV_AUTH_PATH . $path . '.aes',
                $this->oss_expire_time
            ), $fileName];
    }

    public function getNeedSealID()
    {
        $list = AntAuthSealDetail::create()->where([
            'status' => 0,
            'isSeal' => 'true'
        ])->all();
        $ids = [];
        foreach ($list as $item) {
            $ids[$item->getAttr('antAuthId')] = $item->getAttr('antAuthId');
        }
        return $ids;
    }
}
