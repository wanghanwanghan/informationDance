<?php

namespace App\HttpController\Service\QiXiangYun;

use App\HttpController\Service\Common\CommonService;
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
        $this->testAppkey = CreateConf::getInstance()->getConf('qixiangyun.testAppkey');

        $this->secret = CreateConf::getInstance()->getConf('qixiangyun.secret');
        $this->testSecret = CreateConf::getInstance()->getConf('qixiangyun.testSecret');

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
            'client_appkey' => $this->testAppkey,
            'client_secret' => md5($this->testSecret),
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
    function cySync(string $fpdm, string $fphm, string $kprq, float $je, string $jym): array
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

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->testSecret));

        $req_sign = "API-SV1:{$this->testAppkey}:" . $sign;

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
    function ocr(string $base64): array
    {
        $url = $this->testBaseUrl . 'FP/sb';

        $data = [
            'imageData' => $base64,
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->testSecret));

        $req_sign = "API-SV1:{$this->testAppkey}:" . $sign;

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

    //获取发票
    function getInv(string $nsrsbh, string $kpyf, string $jxxbz, string $fplx, string $page): array
    {
        //01	增值税专用发票
        //03	机动车销售统一发票
        //04	增值税普通发票
        //08	增值税电子专用发票
        //10	增值税普票发票（电子）
        //11	增值税普票发票（卷票）
        //14	通行费发票
        //15	二手车统一销售发票
        //17	海关缴款书

        $url = $this->testBaseUrl . 'FP/cj';

        $data = [
            'nsrsbh' => $nsrsbh,
            'kpyf' => $kpyf - 0,//Ym
            'jxxbz' => $jxxbz,//jx xxx
            'fplx' => str_pad($fplx, 2, '0', STR_PAD_LEFT),
            'page' => [
                'pageSize' => 100,
                'currentPage' => $page - 0,
            ],
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->testSecret));

        $req_sign = "API-SV1:{$this->testAppkey}:" . $sign;

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

        CommonService::getInstance()->log4PHP($res);

        return $this->check($res['value']);
    }

    //创建企业
    function createEnt(): array
    {
        $url = $this->testBaseUrl . 'AGG/org/create';

        //{
        //      "nsrsbh": "北京税号",
        //      "aggOrgName": "北京企业名称",
        //      "orgTaxLogin": {
        //        "dq": "11",
        //        "gdsdlfs": "2",
        //        "gdsdlzh": "账号",
        //        "gdsdlmm": "密码",
        //        "grdlfs":"1",
        //        "sflx":"身份类型", //取值：FDDBR法定代表人、CWFZR财务负责人、BSY办税员、LPR领票人
        //        "gryhm":"个人用户名",
        //        "gryhmm":"个人密码",
        //        "zrrsfzh":"个人证件号码"
        //      }
        //    }

        $data = [
            'nsrsbh' => '91110108MA01KPGK0L',
            'aggOrgName' => '北京每日信动科技有限公司',
            'orgTaxLogin' => [
                'dq' => '11',
                'gdsdlfs' => '2',
                'gdsdlzh' => '91110108MA01KPGK0L',
                'gdsdlmm' => '8tMkahzZ',
                'grdlfs' => '1',
                'sflx' => 'FDDBR',//取值：FDDBR法定代表人、CWFZR财务负责人、BSY办税员、LPR领票人
                'gryhm' => '18201611816',
                'gryhmm' => 'Liqi123456',
                'zrrsfzh' => '372328198704051258',
            ]
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->testSecret));

        $req_sign = "API-SV1:{$this->testAppkey}:" . $sign;

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

        CommonService::getInstance()->log4PHP($res);

        return $this->check($res['value']);
    }

    //获取发票下载任务状态
    function getFpxzStatus(): array
    {
        $url = $this->testBaseUrl . 'FP/getFpxzStatus';

        $data = [
            'nsrsbh' => '91110108MA01KPGK0L',
            'kpyf' => 202109,
            'jxxbzs' => [
                'jx', 'xx'
            ],
            'fplxs' => [
                '01', '03', '04', '08', '10', '11', '14', '15', '17'
            ],
            'addJob' => false
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(md5('POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->testSecret));

        $req_sign = "API-SV1:{$this->testAppkey}:" . $sign;

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

        CommonService::getInstance()->log4PHP($res);

        return $this->check($res['value']);
    }

}
