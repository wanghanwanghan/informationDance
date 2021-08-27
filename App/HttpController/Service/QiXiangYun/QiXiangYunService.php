<?php

namespace App\HttpController\Service\QiXiangYun;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;

class QiXiangYunService extends ServiceBase
{
    use Singleton;

    function __construct()
    {
        $this->baseUrl = CreateConf::getInstance()->getConf('qixiangyun.baseUrl');
        $this->testBaseUrl = CreateConf::getInstance()->getConf('qixiangyun.testBaseUrl');
        $this->appkey = CreateConf::getInstance()->getConf('qixiangyun.appkey');
        $this->secret = CreateConf::getInstance()->getConf('qixiangyun.secret');
        return parent::__construct();
    }

    private function check($res): array
    {
        return [
            'code' => 200,
            'paging' => null,
            'result' => $res,
            'msg' => null,
        ];
    }

    private function createToken(): string
    {
        $url = $this->testBaseUrl . 'AGG/oauth2/login';

        $data = [
            'grant_type' => 'client_credentials',
            'client_appkey' => $this->appkey,
            'client_secret' => md5($this->secret),
        ];

        $header = [
            'content-type' => 'application/json;charset=UTF-8'
        ];

        $res = (new CoHttpClient())
            ->useCache(true)->setEx(0.3)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');

        return $res['value']['access_token'];
    }

    //同步查验
    function cySync(string $fpdm, string $fphm, string $kprq, float $je, string $jym)
    {
        $url = $this->testBaseUrl . 'FP/cy';

        $data = [
            'cyList' => [
                [
                    'fpdm' => $fpdm,//发票代码，必填
                    'fphm' => $fphm,//发票号码，必填
                    'kprq' => $kprq,//开票日期，必填，格式 yyyy-MM-dd
                    'je' => $je,//不含税金额，增值税专用发票、增值税电子专用发票、机动车销售统一发票、二手车销售统一发票时必填
                    //校验码后 6 位，增值税普通发票、增值税电子普通发票、增值税普通发票（卷式）、增值税电子普通发票（通行费）时必填
                    'jym' => $jym,
                ]
            ]
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->secret));

        $req_sign = "API-SV1:{$this->appkey}:" . $sign;

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'access_token' => $token,
            'req_date' => $req_date,
            'req_sign' => $req_sign,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');

        return $this->check(current($res['value']));
    }

    //ocr识别
    function ocr(string $base64)
    {
        $url = $this->testBaseUrl . 'FP/sb';

        $data = [
            'imageData' => $base64,
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->secret));

        $req_sign = "API-SV1:{$this->appkey}:" . $sign;

        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'access_token' => $token,
            'req_date' => $req_date,
            'req_sign' => $req_sign,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');

        return $this->check($res['value']);
    }


}
