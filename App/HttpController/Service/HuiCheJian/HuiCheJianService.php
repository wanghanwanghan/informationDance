<?php

namespace App\HttpController\Service\HuiCheJian;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class HuiCheJianService extends ServiceBase
{
    private $sendHeaders;

    function __construct()
    {
        $this->sendHeaders = [
            'content-type' => 'application/x-www-form-urlencoded',
        ];

        return parent::__construct();
    }

    //整理请求结果
    private function checkResp($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['error'] == 0 ? $res['code'] = 200 : $res['code'] = $res['error'];

        isset($res['data']['total']) ? $res['Paging']['total'] = $res['data']['total'] - 0 : $res['Paging'] = null;

        isset($res['data']['lists']) ? $res['Result'] = $res['data']['lists'] : $res['Result'] = null;

        $res['Message'] = $res['msg'];

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['Message']);
    }

    function send($data, $service)
    {
        $url = CreateConf::getInstance()->getConf('huihcejian.createPdfUrl');

        $postData = [
            'entName' => $data['entName'],
            'socialCredit' => $data['socialCredit'],
            'legalPerson' => $data['legalPerson'],
            'idCard' => $data['idCard'],
            'phone' => $data['phone'],
            'region' => $data['region'],
            'address' => $data['address'],
            'requestId' => $data['requestId'],
        ];

        //$res = (new CoHttpClient())->useCache(false)->send($url, $postData, $this->sendHeaders);
        $res = $postData;

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
