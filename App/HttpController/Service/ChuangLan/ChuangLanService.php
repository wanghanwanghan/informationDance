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

    /**
     * 号码状态检测
     * @param $param
     * @return array|mixed|string[]
     */
    function getCheckPhoneStatus($param)
    {
        $url = 'https://api.253.com/open/unn/batch-ucheck';
        $header = ['content-type' => 'application/form-data;charset=UTF-8'];
        $data = [
            'mobiles' => $param['mobiles'],
            'type' => 0,
            'appKey' => $this->appKey,
            'appId' => $this->appId,
        ];
        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($url, http_build_query($data), $header);
        CommonService::getInstance()->log4PHP([$url, $data, $header, $res], 'info', 'getCheckPhoneStatus');
        return $res;
    }
}