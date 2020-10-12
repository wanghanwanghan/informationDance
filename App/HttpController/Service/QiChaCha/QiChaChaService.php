<?php

namespace App\HttpController\Service\QiChaCha;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class QiChaChaService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appkey;
    private $seckey;

    function __construct()
    {
        parent::__construct();
        $this->appkey = CreateConf::getInstance()->getConf('qichacha.appkey');
        $this->seckey = CreateConf::getInstance()->getConf('qichacha.seckey');
    }

    //企查查全羁绊是get请求
    function get($url, $body)
    {
        $time = time();

        $token = strtoupper(md5($this->appkey . $time . $this->seckey));

        $header = ['Token' => $token, 'Timespan' => $time];

        $body['key'] = $this->appkey;

        $url .= '?' . http_build_query($body);

        $resp = (new CoHttpClient())->send($url, $body, $header, [], 'get');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //处理结果给信息controller
    private function checkResp($res)
    {
        if (isset($res['Paging']) && !empty($res['Paging']))
        {
            $res['Paging']=control::changeArrKey($res['Paging'],[
                'PageSize'=>'pageSize',
                'PageIndex'=>'page',
                'TotalRecords'=>'total'
            ]);
        }else
        {
            $res['Paging']=null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500,$res['Paging'],[],'co请求错误');

        return $this->createReturn((int)$res['Status'],$res['Paging'],$res['Result'],$res['Message']);
    }




}
