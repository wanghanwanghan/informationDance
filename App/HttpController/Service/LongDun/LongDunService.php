<?php

namespace App\HttpController\Service\LongDun;

use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\XinDong\XinDongService;
use wanghanwanghan\someUtils\control;

class LongDunService extends ServiceBase
{
    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    private $appkey;
    private $seckey;

    function __construct()
    {
        $this->appkey = CreateConf::getInstance()->getConf('longdun.appkey');
        $this->seckey = CreateConf::getInstance()->getConf('longdun.seckey');

        return parent::__construct();
    }

    //龙盾全羁绊是get请求
    function get($url, $body, array $ext = [])
    {
        $time = time();

        $token = strtoupper(md5($this->appkey . $time . $this->seckey));

        $header = ['Token' => $token, 'Timespan' => $time];

        $body['key'] = $this->appkey;

        $url .= '?' . http_build_query($body);

        $resp = (new CoHttpClient())->send($url, $body, $header, $ext, 'get');
        OperatorLog::addRecord(
            [
                'user_id' => 0,
                'msg' => "url:".@$url." 参数:".@json_encode($body)." 返回：".@json_encode($resp),
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => 'Get请求_LongDunService',
            ]
        );
        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

    //处理结果给信息controller
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
