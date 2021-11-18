<?php

namespace App\HttpController\Service\ShuMeng;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class ShuMengService extends ServiceBase
{
    use Singleton;

    private $ak;
    private $sk;

    function __construct()
    {
        $this->ak = '18618457910';
        $this->sk = 'Quan18618457910';

        return parent::__construct();
    }

    private function check($res): array
    {
        if ($res['retCode'] === '000000' || $res['retCode'] === '000001') {
            $code = 200;
        } else {
            $code = $res['retCode'] - 0;
        }

        if (!empty($res['result']) && isset($res['result']['total'])) {
            $paging['total'] = $res['result']['total'] - 0;
        } else {
            $paging = null;
        }

        if (!empty($res['result']['items'])) {
            $result = $res['result']['items'];
        } elseif (!empty($res['result'])) {
            $result = $res['result'];
        } else {
            $result = null;
        }

        return [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $res['retMsg'] ?? $res['message'] ?? null,
        ];
    }

    private function createSignature(string $ak, string $sk): array
    {
        $time = time() . random_int(100, 999) . '';

        $nonce = random_int(1000, 9999) . '';

        $arr = [
            'applicationId' => $ak,
            'applicationPassword' => $sk,
            'timestamp' => $time,
            'nonce' => $nonce,
            'sign' => '',
        ];

        $sign = hash_hmac('md5', implode('-', [$ak, $time, $nonce]), $sk);

        $arr['sign'] = strtoupper($sign);

        return $arr;
    }

    //采购单位数据查询接口
    function getBidsResult_c(string $entName, string $page, string $type = '精确查询'): array
    {
        $url = 'http://114.115.209.33:18570/bids/getBidsResult_c';

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
        ];

        $data = [
            'signature' => $this->createSignature('bids_proc_211118', 'DZ21NLE-A1CA8'),
            'queryParams' => [
                'cgdwmc' => trim($entName),//采购单位名称
                'cgdwmc_type' => $type,//采购单位名称_查询方式 模糊检索 精确查询
                'page_number' => trim($page) - 0,
                'page_size' => 10,
            ],
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postJson');


        CommonService::getInstance()->log4PHP($data);
        CommonService::getInstance()->log4PHP($res);


        return $this->check($res);
    }


}
