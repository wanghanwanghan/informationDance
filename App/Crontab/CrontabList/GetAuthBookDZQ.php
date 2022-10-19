<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Api\AntAuthSealDetail;
use App\HttpController\Models\Api\DianZiQianAuth;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\FaDaDa\FaDaDaService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\OSS\OSSService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class GetAuthBookDZQ extends AbstractCronTask
{
    public $crontabBase;
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
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $this->currentAesKey = getRandomStr();

        $url_arr = [
            36 => 'https://zkinvoicecommercial.test.dl.alipaydev.com/api/wezTech/collectNotify',//dev
            //36 => 'https://invoicecommercial.test.dl.alipaydev.com/api/wezTech/collectNotify',//dev
            //36 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',//test rsa和dev一样
            41 => 'https://trustdata.antgroup.com/api/wezTech/collectNotify',//pre 和 pro 交换了
            42 => 'https://trustdata-pre.antgroup.com/api/wezTech/collectNotify',//pro 和 pre 交换了
        ];

        $ids = $this->getNeedSealID();//123123

        $ids = implode(',', $ids);
        $idSql = 'dianZiQian_status = 0  and dianZiQian_id>0';
        if (!empty($ids)) {
            $idSql = "id IN ( {$ids} ) OR dianZiQian_status = 0 and dianZiQian_id>0";
        }

        $sql = <<<Eof
SELECT
	* 
FROM
	information_dance_ant_auth_list 
WHERE
	{$idSql}
Eof;
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        if (!empty($list)) {
            CommonService::getInstance()->log4PHP([$list], 'info', 'DZQemptyAntAuthSealDetailDZQ');
            foreach ($list as $oneEntInfo) {
                $data = [
                    'entName' => $oneEntInfo['entName'],// entName companyname
                    'socialCredit' => $oneEntInfo['socialCredit'],//taxno  newtaxno
                    'legalPerson' => $oneEntInfo['legalPerson'],//signName
                    'idCard' => $oneEntInfo['idCard'],
                    'phone' => $oneEntInfo['phone'],//phoneno
                    'city' => $oneEntInfo['city'],//region
                    'regAddress' => $oneEntInfo['regAddress'],//address
                ];

                $DetailList = AntAuthSealDetail::create()->where(['antAuthId' => $oneEntInfo['id'],
                                                                     'dianZiQian_status' => 0])->all();
                $url = [];
                $fileData = [];
                $flieDetail = [];
                if (empty($DetailList) || $oneEntInfo['dianZiQian_id'] >0) {
                    CommonService::getInstance()->log4PHP([$data], 'info', 'DZQemptyAntAuthSealDetail');
                    $u = $this->getDataSealUrl($oneEntInfo['dianZiQian_id']);
                    if(empty($u)){
                        continue;
                    }
                    $url['2'] = $u;
                } else
                {
                    $notNoodIsSeal = [];
                    $detailArr = [];
                    foreach ($DetailList as $value) {
                        $detailArr[$value->orderNo][] = $value;
                        if ($value->getAttr('isSeal') != 'true') {
                            $notNoodIsSeal[$value->orderNo] = $value->orderNo;
                        }
                    }
                    //如果不需要盖章，就去掉这次的请求
                    if (!empty($notNoodIsSeal)) {
                        foreach ($notNoodIsSeal as $item) {
                            unset($detailArr[$item]);
                        }
                    }
                    if (empty($detailArr)) {
                        continue;
                    }
                    $urlD = [];
                    CommonService::getInstance()->log4PHP([$detailArr], 'info', 'DZ$detailArr');
                    foreach ($detailArr as $v) {
                        foreach ($v as $value) {
                            $orderNo = $value->getAttr('orderNo');
                            CommonService::getInstance()->log4PHP([$detailArr], 'info', 'DZ$detailArr');
                            $ur = $this->getSealUrl($value->getAttr('dianZiQian_id'));
                            if(empty($ur)){
                                continue 3;
                            }
                            CommonService::getInstance()->log4PHP([$ur], 'info', 'DZQ$ur');
                            $urlD[$orderNo][$value->getAttr('type')] = $ur;
                            $fileData[$value->getAttr('type')] = [
                                'fileAddress' => '',
                                'type' => $value->getAttr('type') . '',
                                'isSealed' => true,
                                'fileName' => '',
                            ];
                            $flieDetail[$value->getAttr('type')]['fileId'] = $value->getAttr('fileId');
                        }
                    }

                    foreach ($urlD as $orderNo => $order) {

                        foreach ($order as $type => $v) {
                            AntAuthSealDetail::create()->where([
                                   'orderNo' => $orderNo,
                                   'type' => $type,
                                   'antAuthId' => $oneEntInfo['id'],
                               ])->update([
                                              'fileUrl' => $v,
                                              'status' => empty($v) ? 2 : 1,
                                              'dianZiQian_status' => 1
                                          ]);
                            list($file_url, $fileName) = $this->getOssUrl($v, $data['socialCredit'], $flieDetail[$type]);

                            $fileData[$type]['fileAddress'] = $file_url;
                            $fileData[$type]['fileName'] = $fileName;
                            ksort($fileData[$type]);
                        }
                    }
                    CommonService::getInstance()->log4PHP([$urlD], 'info', 'DZQ$url');
                }

                //更新数据库
                AntAuthList::create()->where([
                                                 'entName' => $oneEntInfo['entName'],
                                                 'socialCredit' => $oneEntInfo['socialCredit'],
                                                 'status' => MaYiService::STATUS_0
                                             ])->update([
                                                            'filePath' => $url['2'] ?? '',
                                                            'authDate' => time(),
                                                            'status' => MaYiService::STATUS_1
                                                        ]);
                AntAuthList::create()->where([
                                                 'id' => $oneEntInfo['id'],
                                                 'dianZiQian_id' => $oneEntInfo['dianZiQian_id'],
                                             ])->update([
                                                            'filePath' => $url['2'] ?? '',
                                                            'dianZiQian_status' => 1
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
                    'orderNo' => $oneEntInfo['orderNo'],
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

                CommonService::getInstance()->log4PHP($collectNotify, 'info', 'DZQnotify_auth');

                $url = $url_arr[$id];

                $this->sendAnt($url, $collectNotify);
            }

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'info', 'CrontabList_GetAuthBook');
    }

    public function sendAnt($url, $collectNotify)
    {
        $header = [
            'content-type' => 'application/json;charset=UTF-8',
        ];

        $ret = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, jsonEncode($collectNotify, false), $header, [], 'postjson');

        CommonService::getInstance()->log4PHP($ret, 'info', 'DZQnotify_auth');
    }

    /*
     * 多个文件盖章，是否是只有企业授权书需要去判断是否需要盖章，确定下一个企业是否一定只会有一个是需要盖章的
     */
    public function getSealUrl($dianZiQian_id)
    {
        $data = DianZiQianAuth::create()->where(' id='.$dianZiQian_id)->get();
        if(!empty($data->getAttr('url'))){
            return $data->getAttr('url');
        }
        return '';
    }

    /*
     * 获取需要填充数据和盖章的授权书
     */
    public function getDataSealUrl($dianZiQian_id)
    {
        $data = DianZiQianAuth::create()->where(' id='.$dianZiQian_id)->get();
        if($data->getAttr('entUrlResultCode') == 1){
            if($data->getAttr('personalUrlResultCode')==1){
                return $data->getAttr('url');
            }elseif ($data->getAttr('personalUrlResultCode')==-1){
                return $data->getAttr('url');
            }
        }
        return '';
    }

    public function getOssUrl($path, $socialCredit, $flieDetail)
    {
        if (empty($path)) return '';

        $fileName = $socialCredit . '_' . $flieDetail['fileId'] . '_' . control::getUuid(8);

        $oss = new OSSService();

        return [$oss->doUploadFile(
            $this->oss_bucket,
            Carbon::now()->format('Ym') . DIRECTORY_SEPARATOR . $fileName,
            INV_AUTH_PATH . $path,
            $this->oss_expire_time
        ), $fileName];
    }

    public function getNeedSealID()
    {
        $list = AntAuthSealDetail::create()->where("dianZiQian_status =0 and isSeal='true' and dianZiQian_id>0")->all();
        $ids = [0];
        $idMap = [];
        foreach ($list as $item) {
            $idMap[$item->getAttr('antAuthId')][] = $item->getAttr('id');
            $ids[$item->getAttr('antAuthId')] = $item->getAttr('antAuthId');
        }
        foreach ($idMap as $id => $v) {
            if (count($v) == 2) {
                unset($ids[$id]);
            }
        }
        return $ids;
    }
}
