<?php

namespace App\HttpController\Service\ZhongLianXin;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class ZhongLianXinService extends ServiceBase
{
    function __construct()
    {
        parent::__construct();
    }

    private function checkResp($res): array
    {
        $code = $res['CODE'] - 0;

        $paging = null;

        $result = empty($res['DATA']) ? null : $res['DATA'];

        $msg = $res['MSG'];

        return $this->createReturn($code, $paging, $result, $msg);
    }

    function getDptInstrumentDetail($codetype = '', $instrumentcode = '')
    {
        $post_data = [
            'codetype' => trim($codetype),//注册证编号/备案号类型 0:注册证号或备案号 1:注册证编号 2:备案号
            'instrumentcode' => trim($instrumentcode),//注册证编号/备案号
        ];

        $post_data = array_filter($post_data, function ($val) {
            return !(($val === '' || $val === null));
        });

        $url = $this->test_url . '/dpt/instrument/detail';

        $header = $this->createHeader($this->test_app_id, $this->test_secret);

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($url, $post_data, $header, [], 'get');

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }

}
