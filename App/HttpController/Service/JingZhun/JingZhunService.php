<?php

namespace App\HttpController\Service\JingZhun;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class JingZhunService extends ServiceBase
{
    use Singleton;

    private $token = 'ngQgdJuK98v1J5ND7EclPVHQnMFYdEND';

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


}
