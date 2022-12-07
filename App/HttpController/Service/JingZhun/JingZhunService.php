<?php

namespace App\HttpController\Service\JingZhun;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class JingZhunService extends ServiceBase
{
    use Singleton;

    private $token = 'ngQgdJuK98v1J5ND7EclPVHQnMFYdEND';
    private $url = 'https://data-api.jingdata.com/x/api/';
    private $header = [
        'Content-Type' => 'application/json;charset=UTF-8'
    ];

    //企业发展-投资机构
    //企业发展-投资事件
    //企业发展-融资历史
    //企业发展-竞品信息
    //企业发展-企业业务
    //企业发展-核心团队

    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($res): array
    {
        $code = $pagine = $result = $msg = null;


        return $this->createReturn($code, $pagine, $result, $msg);
    }

    //数据同步
    function dataSync()
    {
        $url = 'https://data-api.jingdata.com/x/api/sync/new_objects';

        $data = [];

        $res = (new CoHttpClient())->useCache(false)->send($url, $data, $this->header, [], 'postJson');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //公司融资事件
    public function enterpriseList($full_name){
        $entInfo = $this->getEntInfo($full_name);
        CommonService::getInstance()->log4PHP($entInfo, 'info', 'enterpriseList');
        dingAlarm('鲸准 公司融资事件',['$entInfo'=>json_encode($entInfo)]);
        if($entInfo['code']!=0){
            return $this->createReturn(200, null, $entInfo['data'], $entInfo['msg']);
        }
        if(empty($entInfo['data']['0']['cid'])){
            return $this->createReturn(500, null, [], '没有查询到这个公司的cid');
        }
        $url = $this->url.'enterprise/finance-list?token='.$this->token.'&id='.$entInfo['data']['0']['cid'];
        $res = (new CoHttpClient())->useCache(false)->send($url, [], $this->header, [], 'GET');
        CommonService::getInstance()->log4PHP($res, 'info', 'enterpriseList');
        return $this->createReturn(200, null, $res['data'], $res['msg']);
    }


    //投资事件
    public function investmentList($full_name){
        $entInfo = $this->getEntInfo($full_name);
        if($entInfo['code']!=0){
            return $this->createReturn(200, null, $entInfo['data'], $entInfo['msg']);
        }
        if(empty($entInfo['data']['0']['cid'])){
            return $this->createReturn(500, null, [], '没有查询到这个公司的cid');
        }
        $url = $this->url.'investment/list?token='.$this->token.'&cid='.$entInfo['data']['0']['cid'];
        $res = (new CoHttpClient())->useCache(false)->send($url, [], $this->header, [], 'GET');
        return $this->createReturn(200, null, $res['data'], $res['msg']);
    }

    //企业搜索
    public function searchComs($full_name){
        $url = $this->url.'enterprise/search-coms?token='.$this->token.'&full_name='.$full_name.'&fuzzy=0';
        $res = (new CoHttpClient())->useCache(false)->send($url, [], $this->header, [], 'GET');
        return $this->createReturn(200, null, $res['data'], $res['msg']);
    }

    private function getEntInfo($full_name){
        $url = $this->url.'enterprise/search-coms?token='.$this->token.'&full_name='.$full_name.'&fuzzy=0';
        CommonService::getInstance()->log4PHP($full_name, 'info', 'enterpriseList');
        return (new CoHttpClient())->useCache(false)->send($url, [], $this->header, [], 'GET');
    }
}
