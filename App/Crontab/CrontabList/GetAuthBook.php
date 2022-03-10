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
        return '11 16 10 * *';
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
        $list = sqlRaw("select * from information_dance_ant_auth_list where id in(".implode(',',$ids).") or (authDate = 0 and status = '".MaYiService::STATUS_0."')", CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $url = [];
        $urlArr = [];
        $fileData = [];
//        $fileIdS = [];
        $flieDetail = [];
        CommonService::getInstance()->log4PHP($list,'info','get_auth_file_list');

        if (!empty($list)) {
            foreach ($list as $oneEntInfo) {
                $data = [
                    'entName' => $oneEntInfo->getAttr('entName'),// entName companyname
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),//taxno  newtaxno
                    'legalPerson' => $oneEntInfo->getAttr('legalPerson'),//signName
                    'idCard' => $oneEntInfo->getAttr('idCard'),
                    'phone' => $oneEntInfo->getAttr('phone'),//phoneno
                    'city' => $oneEntInfo->getAttr('city'),//region
                    'regAddress' => $oneEntInfo->getAttr('regAddress'),//address
                    'requestId' => $oneEntInfo->getAttr('requestId') . time(),//海光用的，没啥用，随便传
                ];
                $DetailList = AntAuthSealDetail::create()->where([
                    'ant_auth_id' => $oneEntInfo->getAttr('id'),
                ])->all();
                CommonService::getInstance()->log4PHP($list,'info','get_auth_file_list_DetailList');

                if(empty($DetailList)){
                    $url['2'] = $this->getDataSealUrl($data);
                }else {
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
                            $urlArr[$value->getAttr('type')] = $value->getAttr('fileAddress');
                        }
                        $fileData[$value->getAttr('type')] = [
                            'fileAddress' => '',
                            'fileSecret' => $value->getAttr('fileSecret'),
                            'type' => $value->getAttr('type'),
                            'isSealed' => (boolean)$value->getAttr('isSealed'),
                            'fileName' => '',
                        ];
                        $flieDetail[$value->getAttr('type')]['fileId'] = $value->getAttr('fileId');
                        $flieDetail[$value->getAttr('type')]['fileSecret'] = $value->getAttr('fileSecret');
                    }
                    //如果不需要盖章，就跳过
                    if($notNoodIsSeal){
                        continue;
                    }
                    foreach ($url as $type => $v) {
                        list($file_url, $fileName) = $this->getOssUrl($v, $data['socialCredit'],$flieDetail[$type]);
                        AntAuthSealDetail::create()->where([
                            'type' => $type,
                            'ant_auth_id' => $oneEntInfo->getAttr('id'),
                        ])->update([
                            'file_url' => $file_url,
                            'status' => empty($file_url)?2:1
                        ]);
                        $fileData[$type]['fileAddress'] = $file_url;
                        $fileData[$type]['fileName'] = $fileName;
                        ksort($fileData[$type]);
                    }
                }
                CommonService::getInstance()->log4PHP($fileData,'info','get_auth_file_list_fileData');

                //更新数据库
                AntAuthList::create()->where([
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                    'status' => MaYiService::STATUS_0
                ])->update([
                    'filePath' => $url['2'],
                    'authDate' => time(),
                    'status' => MaYiService::STATUS_1
                ]);

                //蚂蚁没有传需要盖章的文件过来时，就不需要通知蚂蚁
                if(empty($DetailList)) continue;

                //拿私钥
                $id = $oneEntInfo->getAttr('belong') - 0;
                $info = RequestUserInfo::create()->get($id);
                $rsa_pri_name = $info->getAttr('rsaPri');
                $authResultCode = '0000';
                $body = [
                    'authResultCode' => $authResultCode,
                    'orderNo'=> $oneEntInfo->getAttr('orderNo'),
                    'nsrsbh' => $oneEntInfo->getAttr('socialCredit'),//授权的企业税号
                    'notifyType' => 'AGREEMENT', //通知类型
                    'fileData' => array_values($fileData)
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
                $this->sendAnt($url,$collectNotify);
            }

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

    public function sendAnt($url,$collectNotify){
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
    public function getSealUrl($data,$file_address){
        $data['file_address'] = $file_address;
        $res = (new FaDaDaService())->setCheckRespFlag(true)->getAuthFileForAnt($data);
        CommonService::getInstance()->log4PHP($res,'info','get_auth_file_return_res');
        if ($res['code'] !== 200) {
            $message = ['name'=>'异常内容','msg'=>json_encode($res)];
            dingAlarmMarkdownForWork('法大大授权书接口异常',$message);
            return '';
        }
        return $res['result']['url'];
    }

    /*
     * 获取需要填充数据和盖章的授权书
     */
    public function getDataSealUrl($data){
        $res = (new FaDaDaService())->setCheckRespFlag(true)->getAuthFile($data);
        CommonService::getInstance()->log4PHP($res,'info','get_auth_file_return_res');
        if ($res['code'] !== 200) {
            $message = ['name'=>'异常内容','msg'=>json_encode($res)];
            dingAlarmMarkdownForWork('法大大授权书接口异常',$message);
            return '';
        }
        return $res['result']['url'];
    }

    public function getOssUrl($path,$socialCredit,$flieDetail){
//        $flieDetail['fileSecret'];
        if(empty($path)) return '';
        $fileName = $socialCredit.'_'.$flieDetail['fileId'].'_'.control::getUuid(8).'.pdf';
        return [OSSService::getInstance()
            ->doUploadFile(
                $this->oss_bucket,
                Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR . $fileName,
                INV_AUTH_PATH .$path,
                $this->oss_expire_time
            ),$fileName];
    }

    public function getNeedSealID(){
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
