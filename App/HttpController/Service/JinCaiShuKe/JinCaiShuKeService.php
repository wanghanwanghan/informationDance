<?php

namespace App\HttpController\Service\JinCaiShuKe;

use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Models\Api\JincaiRwhLog;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\moudles\resp\create;

class JinCaiShuKeService extends ServiceBase
{
    public $url;
    public $jtnsrsbh;
    public $appKey;
    public $appSecret;

    function __construct()
    {
        parent::__construct();

        $this->url = 'https://pubapi.jcsk100.com/pre/api/';
        $this->jtnsrsbh = '91110108MA01KPGK0L';
        $this->appKey = '1f58a6db7805';
        $this->appSecret = '3ab58912f92493131aa2';

        return true;
    }

    //
    private function checkResp($res): array
    {
        $res['code'] !== '0000' ?: $res['code'] = 200;
        $arr['content'] = jsonDecode(base64_decode($res['content']));
        $arr['uuid'] = $res['uuid'];
        $res['Result'] = $arr;

        return $this->createReturn($res['code'], $res['Paging'] ?? null, $res['Result'], $res['msg'] ?? null);
    }

    //
    private function signature(array $content, string $nsrsbh, string $serviceid, string $signType): string
    {
        $content = base64_encode(jsonEncode($content, false));

        $arr = [
            'appid' => $this->appKey,
            'content' => $content,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => $nsrsbh,
            'serviceid' => $serviceid,
        ];

        $str = '?';
        array_walk($arr, function ($val, $key) use (&$str) {
            $str .= "{$key}={$val}&";
        });
        $str = rtrim($str, '&');

        return $signType === '0' ?
            base64_encode(hash_hmac('sha256', $str, $this->appSecret, true)) :
            strtoupper(md5(
                $this->appKey .
                $this->appSecret .
                $content .
                $this->jtnsrsbh .
                $nsrsbh .
                $serviceid
            ));
    }

    public function getRwhData(){
        $time = time()-86400;
        $list = JincaiRwhLog::create()->where('status = 0 and created_at<'.$time)->all();
        dingAlarm('金财数科获取发票数据查询时间',['$time'=>$time]);

        if(empty($list)){
            return true;
        }
        foreach ($list as $log){
            $data = $this->S000523( $log->getAttr('nsrsbh'),  $log->getAttr('rwh'),1,1000);
            $content = $data['result']['content'];
            if(empty($content) || $content['sqzt']!=1) {
                dingAlarm('金财数科获取发票数据为空S000523',['$data'=>json_encode($data)]);
                if($content['sqzt'] == 2){
                    JincaiRwhLog::create()->get($log->getAttr('id'))->update(['status'=>2]);
                }
                continue;
            }
            if(empty($content['fpxxs'])){
                continue;
            }
            if(count($content['fpxxs']['data']) == 1000){
                for($i=0;$i<10;$i++){
                    $vdata = $this->S000523( $log->getAttr('nsrsbh'),  $log->getAttr('rwh'),1,1000);
                    $content['fpxxs']['data'] = array_merge($content['fpxxs']['data'],$vdata['result']['content']['fpxxs']['data']);
                    if(count($vdata['result']['content']['fpxxs']['data'])<1000){
                        break;
                    }
                }
            }
//            CommonService::getInstance()->log4PHP($data,'info','http_return_data');
            foreach ($content['fpxxs']['data'] as $val){
                $xmmc = explode('*',trim($val['mxs']['0']['xmmc'],'*'));
                $insert = [
                    'invoiceCode'=>$val['fpdm'],
                    'invoiceNumber'=>$val['fphm'],
                    'billingDate'=>$val['kprq'],
                    'goodsName'=>$xmmc['0'],
                    'totalAmount'=>$val['hjje'],
                    'invoiceType'=>$val['fplx'],
                    'state'=>$val['fpzt'],
                    'salesTaxNo'=>$val['xfsh'],
                    'salesTaxName'=>$val['xfmc'],
                    'purchaserTaxNo'=>$val['gfsh'],
                    'purchaserName'=>$val['gfmc'],
                ];
                if($content['sjlx'] == 2){
                    $invoiceInData = InvoiceIn::create()->where("invoiceCode = '{$insert['invoiceCode']}' and invoiceNumber = '{$insert['invoiceNumber']}'")->get();
                    if(empty($invoiceInData)){
                        InvoiceIn::create()->data($insert)->save();
                    }
                }else{
                    $invoiceOutData = InvoiceOut::create()->where("invoiceCode = '{$insert['invoiceCode']}' and invoiceNumber = '{$insert['invoiceNumber']}'")->get();
                    if(empty($invoiceOutData)){
                        InvoiceOut::create()->data($insert)->save();
                    }
                }
            }
            JincaiRwhLog::create()->get($log->getAttr('id'))->update(['status'=>1]);
        }
    }
    public function get24Month($nsrsbh)
    {
        for ($i = 1; $i <= 24; $i++) {
            $date      = date('Y-m', strtotime('-' . $i . ' month'));
            $startDate = $date . "-01";
            $endDate   = date('Y-m-d', strtotime("$startDate +1 month -1 day"));
            $log = JincaiRwhLog::create()->where("nsrsbh='{$nsrsbh}' and start_date = '{$startDate}'")->get();
            if(!empty($log)){
                continue;
            }
            $res = $this->S000519($nsrsbh, $startDate, $endDate);
            $rwhArr = $res['result']['content'];
            if(empty($rwhArr)){
                dingAlarm('金财数科发票归集数据为空S000519',['$res'=>json_encode($res)]);
                continue;
            }
            foreach ($rwhArr as $value){
                $insertLog = [
                    'rwh' => $value['rwh'],
                    'nsrsbh' => $nsrsbh,
                    'start_date' => $startDate
                ];
                JincaiRwhLog::create()->data($insertLog)->save();
            }
        }
        return $this->createReturn(200, '',  null);
    }
    //发票归集
    function S000519(string $nsrsbh, string $start, string $stop): array
    {
        $content = [
            'sjlxs' => '1,2',//数据类型 1:进项票 2:销项票
            'fplxs' => '01,08,03,04,10,11,14,15',//发票类型 01-增值税专用发票 08-增值税专用发票（电子）03-机动车销售统一发票 ...
            'kprqq' => trim($start),//开票(填发)日期起 YYYY-MM-DD
            'kprqz' => trim($stop),//开票(填发)日期止 日期起止范围必须在同一个月内
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //发票提取
    function S000523(string $nsrsbh, string $rwh, $page, $pageSize): array
    {
        $content = [
            'mode' => '2',
            'rwh' => trim($rwh),
            'page' => trim($page),
            'pageSize' => $pageSize - 0 > 1000 ? '1000' : trim($pageSize),
        ];
        CommonService::getInstance()->log4PHP($content,'info','http_return_data');
        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //发票认证
    function S000514(string $nsrsbh, string $Period, string $BillType, string $DeductibleMode, array $InvoiceList): array
    {
        $content = [
            'Period' => trim($Period),//企业当前税款所属期 YYYYMM
            'BillType' => trim($BillType),//票据类型 0:增值税发票 1:海关缴款书
            'DeductibleMode' => trim($DeductibleMode),//1:抵扣勾选；(默认为1) -1:取消抵扣勾选； 2:退税认证； 4：不抵扣勾选； -4：取消不抵扣勾选；
            'InvoiceList' => $InvoiceList,//发票数据 最多100张发票
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

}


