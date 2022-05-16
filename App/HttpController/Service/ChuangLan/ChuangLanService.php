<?php

namespace App\HttpController\Service\ChuangLan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ChuangLanService extends ServiceBase
{
    public $appId;
    public $appKey;

    function __construct()
    {
        parent::__construct();
        $this->appKey = 'mUoCN8pT';
        $this->appId = 'Lz8AqXxJ';
        return true;
    }

    static function getStatusCnameMap(){
        return [
            1 => '正常',
            2 => '停机',
            3 => '在网但不可用',
            4 => '不在网',
            5 => '无短信能力',
            6 => '欠费',
            7 => '长时间关机',
            8 => '销号/未启用',
            9 => '服务器异常',
            10 => '查询失败',
        ]  ;
    }

    /**
     * 号码状态检测
     * @param $param
     * @return array|mixed|string[]
     */
    function getCheckPhoneStatus($param)
    {
        $url = 'https://api.253.com/open/unn/batch-ucheck';
        $header = [
//            'content-type' => 'application/form-data;charset=UTF-8'
        ];
        $data = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'mobiles' => $param['mobiles'], // 检测手机号，多个手机号码用英文半角逗号隔开，仅支持国内号码
            'type' => 0,
        ];
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $header);
        CommonService::getInstance()->log4PHP([$url, $data, $header, $res], 'info', 'getCheckPhoneStatus');
        return $res;
    }

    function mobileNetStatus($param){
        $url = 'https://api.253.com/open/zwsjmd/mobile_netstatus';
        $header = [
//            'content-type' => 'application/form-data;charset=UTF-8'
        ];
        $data = [
            'appId' => $this->appId,
            'appKey' => $this->appKey,
            'mobile' => $param['mobile']
        ];
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $data, $header);
        CommonService::getInstance()->log4PHP([$url, $data, $header, $res], 'info', 'mobile_netstatus');
        return $res;
    }
}