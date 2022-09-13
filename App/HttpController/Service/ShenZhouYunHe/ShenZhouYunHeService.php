<?php

namespace App\HttpController\Service\ShenZhouYunHe;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class ShenZhouYunHeService extends ServiceBase
{
    public $url;
    public $AccessKeyID;
    public $AccessKeySecret;
    public $SignatureNonce;
    public $TimeStamp;
    public $Version;
    public $curl_use_cache;

    function __construct($type = '')
    {
        parent::__construct();
        $this->checkRespFlag = true;
        //test
        $this->url        = 'https://jxp-ports-fp.bigfintax.com/jxp/';
        $this->AccessKeyID = CreateConf::getInstance()->getConf('shenzhouyunhe.AccessKeyID');
        $this->AccessKeySecret = CreateConf::getInstance()->getConf('shenzhouyunhe.AccessKeySecret_TEST');
        $this->TimeStamp      = str_replace(' ','T',date('Y-m-d H:i:s',time())).'Z';
        $this->curl_use_cache = false;
        return true;
    }

    private function getData($postData){
        $Signature = $this->getSignature();
        $url = '?Version='.$this->Version.'&AccessKeyID='.$this->AccessKeyID.'&TimeStamp='.$this->TimeStamp.'&SignatureNonce='.$this->SignatureNonce.'&Signature='.$Signature;

        $Content = [
            'taxNum'=>$postData['taxNum']??'',//'91110108MA01KPGK0L',//公司税号
            'reportPeriod' => $postData['reportPeriod']??'',//'2022-03',//税款所属期
            'billingDateStart'=>$postData['billingDateStart']??'',//'2022-07-08',//开票日期起
            'billingDateEnd' => $postData['billingDateEnd']??'',//'2022-09-08',//开票日期止
            'invoiceType'=>$postData['invoiceType']??'',//'All',//发票类型  ALL:全部发票，01：增值税专用发票，03：机动车销售统一发票，04：增值税普通发票，05：增值税电子专用发票，10：增值税电子普通发票，11：卷式发票，14：通行费，15：二手车发票
            'sjlx' => $postData['sjlx']??'',//'1',//数据类型    “1”进项票 “2”销项票
            'pageNum' => $postData['pageNum']??'',//1,
            'pageSize' => $postData['pageSize']??'',//100
        ];
        $data = ['RequestID'=>'mrxd'.date('YmdHis').control::getUuid(8),'Content'=>$Content];
        return [$url,$data];
//        request($url,json_encode($data));
    }

    private function getSignature(){
        $this->SignatureNonce = control::getUuid();
        $str = 'AccessKeyID='.$this->AccessKeyID.'&SignatureNonce='.$this->SignatureNonce.'&TimeStamp='.$this->TimeStamp.'&Version='.$this->Version;
        return strtoupper(md5(hash_hmac('sha1', $str,strtoupper(md5($this->AccessKeySecret)),true )));
    }

    /**
     * 发票归集
     */
    public function invoices($postData)
    {

        $path  = "/collect/invoices";
        list($url,$data) = $this->getData($postData);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path.$url, $data, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        dingAlarm('invoices接口入参，返回',['url'=>$this->url . $path.$url,'$data'=>json_encode($data),'$resp'=>json_encode($resp)]);
        CommonService::getInstance()->log4PHP([$this->url . $path, $data,$resp], 'info', 'shenzhou_invoices');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    /**
     * 进项发票采集接口
     */
    public function collection($postData)
    {

        $path  = "/invoice/collection";
        list($url,$data) = $this->getData($postData);
        $resp  = (new CoHttpClient())
            ->useCache($this->curl_use_cache)
            ->send($this->url . $path.$url, $data, $this->getHeader('json'), ['enableSSL' => true], 'postjson');
        CommonService::getInstance()->log4PHP([$this->url . $path, $data,$resp], 'info', 'shenzhou_collection');
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    private function checkResp(array $res): array
    {
        if (isset($res['Code']) && $res['Code'] == '200') {
            $code = 200;
        } else {
            $code = $res['Code'] - 0;
        }

        $paging = null;

        $result = $res['Content'] ?? null;

        $msg = $res['Message'] ?? null;

        return $this->createReturn($code, $paging, $result, $msg);
    }
    private function getHeader(string $type = ''): array
    {
        switch (strtolower($type)) {
            case 'json':
                return ['Content-Type' => 'application/json;'];
            case 'file':
                return ['Content-Type' => 'multipart/form-data;'];
            default:
                return [];
        }
    }
}