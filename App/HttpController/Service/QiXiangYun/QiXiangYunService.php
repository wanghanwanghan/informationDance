<?php

namespace App\HttpController\Service\QiXiangYun;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;

class QiXiangYunService extends ServiceBase
{
    use Singleton;

    private $baseUrl;
    private $testBaseUrl;
    private $appkey;
    private $testAppkey;
    private $secret;
    private $testSecret;

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
        $url = $this->baseUrl . 'AGG/oauth2/login';

        $data = [
            'grant_type' => 'client_credentials',
            'client_appkey' => $this->appkey,
            'client_secret' => md5($this->secret),
        ];
        CommonService::getInstance()->log4PHP($this->secret, 'info', 'qixiangyun_createTokenParamSecret');
        $header = [
            'content-type' => 'application/json;charset=UTF-8'
        ];
        CommonService::getInstance()->log4PHP([$url, $data, $header], 'info', 'qixiangyun_createTokenParam');
        $res = (new CoHttpClient())
            ->useCache(false)->setEx(0.3)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');
        CommonService::getInstance()->log4PHP($res, 'info', 'qixiangyun_createToken');
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

        $url = $this->baseUrl . 'FP/cj';

        $data = [
            'nsrsbh' => $nsrsbh,//91110108MA01KPGK0L',
            'kpyf' => $kpyf - 0,//Ym
            'jxxbz' => $jxxbz,//jx xx
            'fplx' => str_pad($fplx, 2, '0', STR_PAD_LEFT),
            'page' => [
                'pageSize' => 100,
                'currentPage' => $page - 0,
            ],
        ];

        $req_date = time() . '000';

        $token = $this->createToken();

        $sign = base64_encode(
            md5(
                'POST_' . md5(json_encode($data)) . '_' . $req_date . '_' . $token . '_' . $this->secret
            )
        );

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

        CommonService::getInstance()->log4PHP([$url, $data, $header,$res],'info','getInv');

        return $this->check($res['value']);
    }

    //创建企业
    function createEnt(): array
    {
        $url = $this->baseUrl . 'AGG/org/create';

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
                'gryhmm' => 'liqi123456!',
                'zrrsfzh' => '372328198704051258',
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
        CommonService::getInstance()->log4PHP([$url, $data, $header,$res], 'info', 'createEnt_Param_res:q');

//        CommonService::getInstance()->log4PHP($res);

        return $this->check($res['value']);
    }

    //获取发票下载任务状态
    function getFpxzStatus(string $nsrsbh, $kpyf = ''): array
    {
        $res = [];
        //获取一个月的
        if (!empty($kpyf)) {
            $this->actionGetFpxzStatus($nsrsbh, $kpyf);
            return $res;
        }
        //获取24个月的数据
        for ($i = 1; $i <= 24; $i++) {
            $kpyf = Carbon::now()->subMonths($i)->format('Ym');
            $this->actionGetFpxzStatus($nsrsbh, $kpyf);
        }
        return $res;
    }

    /**
     * 更具月份获取企享云通过税务局查询发票进度
     * @param $nsrsbh
     * @param $kpyf
     * @return void
     */
    function actionGetFpxzStatus($nsrsbh, $kpyf)
    {
        $url = $this->baseUrl . 'FP/getFpxzStatus';
        $data = [
            'nsrsbh' => $nsrsbh,
            'kpyf' => $kpyf - 0,//Ym
            'jxxbzs' => ['jx', 'xx'],
            'fplxs' => ['01', '03', '04', '08', '10', '11', '14', '15', '17'],
            'addJob' => true //true发起归集，FALSE 查询上次归集状态
        ];

        $req_date = time() . mt_rand(100, 999);
        $token = $this->createToken();
        $sign = base64_encode(
            md5('POST_' . md5(jsonEncode($data, false)) . '_' . $req_date . '_' . $token . '_' . $this->secret)
        );
        $req_sign = "API-SV1:{$this->appkey}:" . $sign;
        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'access_token' => $token,
            'req_date' => $req_date,
            'req_sign' => $req_sign,
        ];
//        CommonService::getInstance()->log4PHP([$url, $data, $header], 'info', 'actionGetFpxzStatusParam');
        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');
//        CommonService::getInstance()->log4PHP([$url, $data, $header,$res], 'info', 'actionGetFpxzStatusParam');

        CommonService::getInstance()->log4PHP($res);
    }

    public function getCjYgxByFplxs($postData)
    {
        $nsrsbh = $postData['nsrsbh'];
        $skssq = $postData['skssq'];
        $resData = [];
        $fplxs = ['01', '03',  '08',   '14',  '17'];
        foreach ($fplxs as $fplx) {
            $res = $this->cjYgx($nsrsbh, $fplx, $skssq, 1);
            if ($res['result']['success']) {
                $resData = array_merge($resData, $res['value']['list']);
            } else{
                dingAlarm('已勾选发票归集异常', ['$res' => json_encode($res)]);
            }
            if ($res['value']['page']['totalPage'] > 1) {
                for ($i = 2; $i <= $res['value']['page']['totalPage']; $i++) {
                    $res2 = $this->cjYgx($nsrsbh, $fplx, $skssq, $i);
                    if ($res2['result']['success']) {
                        $resData = array_merge($resData, $res2['value']['list']);
                    }else{
                        dingAlarm('已勾选发票归集异常', ['$res' => json_encode($res)]);
                    }
                }
            }

        }
        return $resData;
    }

    public function cjYgx($nsrsbh, $fplx, $skssq, $currentPage)
    {
        $url = $this->baseUrl . 'FP/cjYgx';
        $data = [
            'nsrsbh' => $nsrsbh,
            'fplx' => $fplx,
            'skssq' => $skssq, //税款所属期，格式yyyyMM，202010
            'page' => [
                'pageSize' => 20,
                'currentPage' => $currentPage
            ]
        ];
        $req_date = time() . mt_rand(100, 999);
        $token = $this->createToken();
        $sign = base64_encode(
            md5('POST_' . md5(jsonEncode($data, false)) . '_' . $req_date . '_' . $token . '_' . $this->secret)
        );
        $req_sign = "API-SV1:{$this->appkey}:" . $sign;
        $header = [
            'content-type' => 'application/json;charset=UTF-8',
            'access_token' => $token,
            'req_date' => $req_date,
            'req_sign' => $req_sign,
        ];
//        CommonService::getInstance()->log4PHP([$url, $data, $header], 'info', 'actionGetFpxzStatusParam');
        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($url, $data, $header, [], 'postjson');
        CommonService::getInstance()->log4PHP([$url, $data, $header, $res], 'info', 'cjYgx_ret');
        return $res;
    }
}
