<?php

namespace App\HttpController\Service\HuiCheJian;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\utils\arr;

class HuiCheJianService extends ServiceBase
{
    public $appId;

    function __construct()
    {
        $this->appId = CreateConf::getInstance()->getConf('huichejian.appId');
        return parent::__construct();
    }

    //整理请求结果
    private function checkResp($res): array
    {
        if (isset($res['coHttpErr'])) return $this->createReturn(500, null, [], 'co请求错误');

        $res['paging'] = null;

        if (!empty($res['encrypt']) && !empty($res['content'])) {
            $res['result'] = $this->afterSendEncrypt($res, $this->appId);
        }

        return $this->createReturn($res['code'] - 0, $res['paging'], $res['result'], $res['msg']);
    }

    function getAuthPdf($data): array
    {
        $url = CreateConf::getInstance()->getConf('huichejian.getPdfUrl');

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

        $postData = $this->beforeSendEncrypt($postData, $this->appId);

        $res = (new CoHttpClient())->useCache(false)
            ->send($url, $postData, [], [], 'postjson');

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }


}
