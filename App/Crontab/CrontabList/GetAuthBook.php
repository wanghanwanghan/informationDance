<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntAuthSealDetail;
use App\HttpController\Models\Api\AntEmptyLog;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
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
        return '* * * * *';
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
        //准备获取授权书的企业列表
        $list = AntAuthList::create()->where([
            'authDate' => 0,
            'status' => MaYiService::STATUS_0,
        ])->all();

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
                $url = [];
                $urlArr = [];
                foreach ($DetailList as $value){
                    if($value->getAttr('is_seal')){
                        if($value->getAttr('type') !=2 ){
                            $url[$value->getAttr('type')] = $this->getSealUrl($data,$value->getAttr('file_address'));
                        }else{//数字代办委托书盖章加填充
                            $url['2'] = $this->getDataSealUrl($data);
                        }
                    }else{
                        $urlArr[] = $value->getAttr('file_address');
                    }
                }
                foreach ($url as $type => $v){
                    $file_url = $this->getOssUrl($v,$data['socialCredit']);
                    AntAuthSealDetail::create()->where([
                        'type' => $type,
                        'ant_auth_id' => $oneEntInfo->getAttr('id'),
                    ])->update([
                        'file_url' => $file_url,
                    ]);
                    $urlArr[] = $file_url;
                }

                //更新数据库
                AntAuthList::create()->where([
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                ])->update([
                    'filePath' => $url,
                    'authDate' => time(),
                    'status' => MaYiService::STATUS_1
                ]);
                $lastReqTime = $oneEntInfo->getAttr('lastReqTime');
                //拿私钥
                $id = $oneEntInfo->getAttr('belong') - 0;
                $info = RequestUserInfo::create()->get($id);
                $rsa_pub_name = $info->getAttr('rsaPub');
                $rsa_pri_name = $info->getAttr('rsaPri');
                $authResultCode = '0000';
                //拿公钥加密
                $stream = file_get_contents(RSA_KEY_PATH . $rsa_pub_name);
                //AES加密key用RSA加密
                $fileSecret = control::rsaEncrypt($this->currentAesKey, $stream, 'pub');
//                $urlArr = $this->getFlieUrl($oneEntInfo->getAttr('id'));
                $fileKeyList = empty($urlArr) ? [] : array_filter($urlArr);
                $body = [
                    'nsrsbh' => $oneEntInfo->getAttr('socialCredit'),//授权的企业税号
                    'fileSecret' => $fileSecret,//对称钥秘⽂
                    'companyName' => $oneEntInfo->getAttr('entName'),//公司名称
                    'authTime' => date('Y-m-d H:i:s', $oneEntInfo->getAttr('requestDate')),//授权时间
                    'totalCount' => count($urlArr) . '',
                    'fileKeyList' => $fileKeyList,//文件路径
                    'type' => 'AGREEMENT' //通知类型
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
    public function getFlieUrl($ant_auth_id){
        $list = AntAuthSealDetail::create()->where([
            'ant_auth_id' => $ant_auth_id,
        ])->all();
        if(empty($list)) return [];
        $urlArr = [];
        foreach ($list as $item) {
            $urlArr[] = $item->getAttr('file_url');
        }
        return $urlArr;
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
            dingAlarmMarkdown('法大大授权书接口异常',$message);
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
            dingAlarmMarkdown('法大大授权书接口异常',$message);
            return '';
        }
        return $res['result']['url'];
    }

    public function getOssUrl($path,$socialCredit){
        if(empty($path)) return '';
        return OSSService::getInstance()
            ->doUploadFile(
                $this->oss_bucket,
                Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR . $socialCredit.'page'.control::getUuid().'pdf',
                INV_AUTH_PATH .$path,
                $this->oss_expire_time
            );
    }
}
