<?php

namespace App\HttpController\Service\DaTong;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class DaTongService extends ServiceBase
{
    private $base_url = 'https://api.biaoxun.cn/api/search/find';
    private $accessKey;
    private $secretKey;

    function __construct()
    {
        $this->accessKey = CreateConf::getInstance()->getConf('datong.ztb_accessKey');
        $this->secretKey = CreateConf::getInstance()->getConf('datong.ztb_secretKey');

        return parent::__construct();
    }

    private function checkResp($res): array
    {
        return $this->createReturn($res['code'], $res['data']['index'] ?? null, $res['data'], $res['msg']);
    }

    private function extractFields($resp, $userid)
    {
        switch ($userid) {
            case 72:
                // 小飞
                $model = [
                    'project' => [
                        'BID_NAME',
                        'BID_NUM',
                    ],
                    'base' => [
                        'BID_TYPE',
                    ],
                    'bidsections' => [
                        'BID_OPENING_TM',
                        'GAIN_BID_FILE_CLOSE_TM',
                        'BIDDER_QUALIFICATIONS',
                        'EVAL_BID_METHOD',
                    ],
                    'bond' => [
                        'BID_BOND_AMT_MODE',
                        'BID_BOND_AMT',
                    ],
                    'buyer' => [
                        'BUYER_NAME',
                        'BUYER_USER',
                        'BUYER_CONTACT',
                        'BUYER_ADDR',
                    ],
                    'agent' => [
                        'AGENT_NAME',
                        'AGENT_USER',
                        'AGENT_CONTACT',
                        'AGENT_ADDR',
                    ],
                ];
                foreach ($resp['data']['list'] as $key => $val) {
                    foreach ($val as $k => $v) {
                        if (!key_exists($k, $model)) {
                            unset($resp['data']['list'][$key][$k]);
                            continue;
                        }
                        if (is_array(current($v))) {
                            foreach ($v as $c_index => $c_arr) {
                                $resp['data']['list'][$key][$k][$c_index] = array_intersect_key($c_arr, array_flip($model[$k]));
                            }
                        } else {
                            foreach ($v as $c_k => $c_v) {
                                if (!in_array($c_k, $model[$k], true)) {
                                    unset($resp['data']['list'][$key][$k][$c_k]);
                                }
                            }
                        }
                    }
                }
                break;
            default:
        }
        return $resp;
    }

    function getList(array $data, $userid)
    {
        $randomStr = control::getUuid();
        $time = microTimeNew();

        $sign = md5($this->accessKey . $time . $randomStr . $this->secretKey);

        $header = [
            'key' => $this->accessKey,
            'timestamp' => $time,
            'randomStr' => $randomStr,
            'sign' => $sign,
        ];

        $resp = (new CoHttpClient())
            ->useCache(false)
            ->send($this->base_url, $data, $header, [], 'postjson');

        // 根据不同userid给不同字段
        if (!empty($resp['data']['list'])) {
            $resp = $this->extractFields($resp, $userid);
        }

        return $this->checkRespFlag ? $this->checkResp($resp) : $resp;
    }


}
