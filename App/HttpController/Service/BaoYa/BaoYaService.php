<?php

namespace App\HttpController\Service\BaoYa;

use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\XinDong\XinDongService;
use wanghanwanghan\someUtils\control;

class BaoYaService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appkey;
    private $seckey;
    private $debug = false;

    function  setDebug($res){
        $this->debug = $res;
    }
    function __construct()
    {
        $this->appkey = CreateConf::getInstance()->getConf('longdun.appkey');
        $this->seckey = CreateConf::getInstance()->getConf('longdun.seckey');

        return parent::__construct();
    }

    function getProducts(){
        $url =  CreateConf::getInstance()->getConf('baoya.products_url');
        if($this->debug){
            $url =  CreateConf::getInstance()->getConf('baoya.products_url_test');
        }
        return $this->get($url,'');
    }

    function getProductDetail($id){
        $url =  CreateConf::getInstance()->getConf('baoya.products_detail_url').'/'.$id;
        if($this->debug){
            $url =  CreateConf::getInstance()->getConf('baoya.products_detail_url_test').'/'.$id;
        }
        return $this->get($url,'');
    }

    //龙盾全羁绊是get请求
    function get($url, $body,array $header = [], array $ext = [])
    {
        $time = time();
        //$token = strtoupper(md5($this->appkey . $time . $this->seckey));

        //$header = ['Token' => $token, 'Timespan' => $time];

        //$body['key'] = $this->appkey;

        //$url .= '?' . http_build_query($body);

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $body, $header, $ext, 'get');
//        OperatorLog::addRecord(
//            [
//                'user_id' => 0,
//                'msg' => "url:".@$url." 参数:".@json_encode($body)." 返回：".@json_encode($resp),
//                'details' =>json_encode( XinDongService::trace()),
//                'type_cname' => 'Get请求_BaoYaService',
//            ]
//        );
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //处理结果给信息controller
    /**

     */
    private function checkResp($res)
    {


        if (isset($res['Paging']) && !empty($res['Paging'])) {
            $res['Paging'] = control::changeArrKey($res['Paging'], [
                'PageSize' => 'pageSize',
                'PageIndex' => 'page',
                'TotalRecords' => 'total'
            ]);
        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        return $this->createReturn((int)$res['Status'], $res['Paging'], $res['Result'], $res['Message']);
    }


}
