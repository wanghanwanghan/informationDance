<?php

namespace App\HttpController\Service\JinCaiShuKe;

use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Models\Api\JincaiRwhLog;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\XinDong\XinDongService;
use wanghanwanghan\someUtils\control;
use wanghanwanghan\someUtils\moudles\resp\create;

class JinCaiShuKeService extends ServiceBase
{
    public $url;
    public $jtnsrsbh;
    public $appKey;
    public $appSecret;
    public $oauthToken;
    public $wupan_url;

    static public $province = [
        '北京' => 'beijing',
        '天津' => 'tianjin',
        '河北' => 'hebei',
        '山西' => 'shanxi',
        '内蒙古' => 'neimenggu',
        '辽宁' => 'liaoning',
        '大连' => 'dalian',
        '吉林' => 'jilin',
        '黑龙江' => 'heilongjiang',
        '上海' => 'shanghai',
        '江苏' => 'jiangsu',
        '浙江' => 'zhejiang',
        '宁波' => 'ningbo',
        '安徽' => 'anhui',
        '江西' => 'jiangxi',
        '福建' => 'fujian',
        '厦门' => 'xiamen',
        '山东' => 'shandong',
        '青岛' => 'qingdao',
        '河南' => 'henan',
        '湖北' => 'hubei',
        '湖南' => 'hunan',
        '广东' => 'guangdong',
        '深圳' => 'shenzhen',
        '广西' => 'guangxi',
        '海南' => 'hainan',
        '重庆' => 'chongqing',
        '四川' => 'sichuan',
        '贵州' => 'guizhou',
        '云南' => 'yunnan',
        '西藏' => 'xizang',
        '陕西' => 'shaanxi',
        '甘肃' => 'gansu',
        '青海' => 'qinghai',
        '宁夏' => 'ningxia',
        '新疆' => 'xinjiang',
    ];

    function __construct()
    {
        parent::__construct();

        $this->url = 'https://pubapi.jcsk100.com/pre/api/';
        $this->jtnsrsbh = '91110108MA01KPGK0L';
        $this->appKey = CreateConf::getInstance()->getConf('jincai.appKey');
        $this->appSecret = CreateConf::getInstance()->getConf('jincai.appSecret');

        $this->wupan_url = 'http://ctp.jcsk100.com/';
        $this->oauthToken = CreateConf::getInstance()->getConf('jincai.oauthToken');

        return true;
    }

    //
    private function checkResp($res, string $type = ''): array
    {
        if ($type === 'wupan') {
            return $this->createReturn($res['code'],
                null,
                jsonDecode(base64_decode($res['data'])),
                $res['msg']);
        } else {
            $res['code'] !== '0000' ?: $res['code'] = 200;
            $arr['content'] = jsonDecode(base64_decode($res['content']));
            $arr['uuid'] = $res['uuid'];
            $res['Result'] = $arr;
            return $this->createReturn($res['code'], $res['Paging'] ?? null, $res['Result'], $res['msg'] ?? null);
        }
    }

    //
    private function signature(array $content, string $nsrsbh, string $serviceid, string $signType): string
    {
        $content = base64_encode(jsonEncode($content, false));

        $arr = [
            'appid' => $this->appKey,
            'content' => $content,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => $nsrsbh,
            'serviceid' => $serviceid,
        ];

        $str = '?';
        array_walk($arr, function ($val, $key) use (&$str) {
            $str .= "{$key}={$val}&";
        });
        $str = rtrim($str, '&');

        return $signType === '0' ?
            base64_encode(hash_hmac('sha256', $str, $this->appSecret, true)) :
            strtoupper(md5(
                $this->appKey .
                $this->appSecret .
                $content .
                $this->jtnsrsbh .
                $nsrsbh .
                $serviceid
            ));
    }

    function getRwhData()
    {
        $time = time() - 86400;
        $list = JincaiRwhLog::create()->where('status = 0 and created_at<' . $time)->all();
//        dingAlarm('金财数科获取发票数据查询时间', ['$time' => $time]);

        if (empty($list)) {
            return true;
        }
        foreach ($list as $log) {
            $data = $this->S000523($log->getAttr('nsrsbh'), $log->getAttr('rwh'), 1, 500);
            $content = $data['result']['content'];
            if (empty($content) || $content['sqzt'] != 1) {
//                CommonService::getInstance()->log4PHP($data, 'info', 'getRwhData_error');
//                dingAlarm('金财数科获取发票数据为空S000523', ['$data' => json_encode($data)]);
                if ($content['sqzt'] == 2) {
                    JincaiRwhLog::create()->get($log->getAttr('id'))->update(['status' => 2]);
                }
                continue;
            }
            if (empty($content['fpxxs'])) {
                continue;
            }
            $resDataAll = $content['fpxxs']['data'];
            if (count($content['fpxxs']['data']) == 500) {
                for ($i = 0; $i < 50; $i++) {
                    $vdata = $this->S000523($log->getAttr('nsrsbh'), $log->getAttr('rwh'), 1, 500);
                    $resDataAll = array_merge($resDataAll, $vdata['result']['content']['fpxxs']['data']);
                    if (count($vdata['result']['content']['fpxxs']['data']) < 500) {
                        break;
                    }
                }
            }
//            CommonService::getInstance()->log4PHP($data,'info','http_return_data');
            foreach ($resDataAll as $val) {
                $xmmc = '';
                if (isset($val['mxs']['0']['xmmc'])) {
                    $xmmc = explode('*', trim($val['mxs']['0']['xmmc'], '*'));
                } else if (isset($val['cllx'])) {
                    $xmmc = $val['cllx'];
                } else if ($val['fplx'] == 15) {
                    $xmmc = '二手车';
                } else if (isset($val['mxs']['0']['hwmc'])) {
                    $xmmc = explode('*', trim($val['mxs']['0']['hwmc'], '*'));
                } else {
                    CommonService::getInstance()->log4PHP($data, 'info', 'getRwhData_empty_goodsname');
                }

                $insert = [
                    'invoiceCode' => $val['fpdm'],
                    'invoiceNumber' => $val['fphm'],
                    'billingDate' => $val['kprq'],
                    'goodsName' => $xmmc['0'],
                    'totalAmount' => $val['hjje'],
                    'invoiceType' => $val['fplx'],
                    'state' => $val['fpzt'],
                    'salesTaxNo' => $val['xfsh'] ?? $val['mfdwdm'],
                    'salesTaxName' => $val['xfmc'] ?? $val['mfdw'],
                    'purchaserTaxNo' => $val['gfsh'] ?? $val['gfdwdm'],
                    'purchaserName' => $val['gfmc'] ?? $val['gfdw'],
                ];
                if ($content['sjlx'] == 1) {
                    $invoiceInData = InvoiceIn::create()->where("invoiceCode = '{$insert['invoiceCode']}' and invoiceNumber = '{$insert['invoiceNumber']}'")->get();
                    if (empty($invoiceInData)) {
                        InvoiceIn::create()->data($insert)->save();
                    }
                } else {
                    $invoiceOutData = InvoiceOut::create()->where("invoiceCode = '{$insert['invoiceCode']}' and invoiceNumber = '{$insert['invoiceNumber']}'")->get();
                    if (empty($invoiceOutData)) {
                        InvoiceOut::create()->data($insert)->save();
                    }
                }
            }
            JincaiRwhLog::create()->get($log->getAttr('id'))->update(['status' => 1]);
        }
    }

    function get24Month($nsrsbh)
    {
        for ($i = 1; $i <= 36; $i++) {
            $date = date('Y-m', strtotime('-' . $i . ' month'));
            $startDate = $date . "-01";
            $endDate = date('Y-m-d', strtotime("$startDate +1 month -1 day"));
            $log = JincaiRwhLog::create()->where("nsrsbh='{$nsrsbh}' and start_date = '{$startDate}'")->get();
            if (!empty($log)) {
                continue;
            }
            $res = $this->S000519($nsrsbh, $startDate, $endDate);
            $rwhArr = $res['result']['content'];
            if (empty($rwhArr)) {
                dingAlarm('金财数科发票归集数据为空S000519', ['$res' => json_encode($res)]);
                continue;
            }
            foreach ($rwhArr as $value) {
                $insertLog = [
                    'rwh' => $value['rwh'],
                    'nsrsbh' => $nsrsbh,
                    'start_date' => $startDate
                ];
                JincaiRwhLog::create()->data($insertLog)->save();
            }
        }
        return $this->createReturn(200, '', null);
    }

    //api 发票归集
    function S000519(string $nsrsbh, string $start, string $stop): array
    {
        $content = [
            'sjlxs' => '1,2',//数据类型 1:进项票 2:销项票
            'fplxs' => '01,08,03,04,10,11,14,15',//发票类型 01-增值税专用发票 08-增值税专用发票（电子）03-机动车销售统一发票 ...
            'kprqq' => trim($start),//开票(填发)日期起 YYYY-MM-DD
            'kprqz' => trim($stop),//开票(填发)日期止 日期起止范围必须在同一个月内
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //api 查询状态接口
    function S000520(string $nsrsbh, string $rwh)
    {
        $content = [
            'requuid' => control::getUuid(),//请求流水号
            'rwh' => trim($rwh),//任务号
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //api 发票提取
    function S000523(string $nsrsbh, string $rwh, $page, $pageSize): array
    {
        $content = [
            'mode' => '2',
            'rwh' => trim($rwh),
            'page' => trim($page),
            'pageSize' => $pageSize,
        ];
//        CommonService::getInstance()->log4PHP($content,'info','http_return_data');
        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);
        OperatorLog::addRecord(
            [
                'user_id' => 0,
                'msg' => "url:" . $this->url . " 参数:" . @json_encode($post_data) . " 返回：" . @json_encode($res),
                'details' => json_encode(XinDongService::trace()),
                'type_cname' => '发票提取_S000523',
            ]
        );
        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //api 发票认证
    function S000514(string $nsrsbh, string $Period, string $BillType, string $DeductibleMode, array $InvoiceList): array
    {
        $content = [
            'Period' => trim($Period),//企业当前税款所属期 YYYYMM
            'BillType' => trim($BillType),//票据类型 0:增值税发票 1:海关缴款书
            'DeductibleMode' => trim($DeductibleMode),//1:抵扣勾选；(默认为1) -1:取消抵扣勾选； 2:退税认证； 4：不抵扣勾选； -4：取消不抵扣勾选；
            'InvoiceList' => $InvoiceList,//发票数据 最多100张发票
        ];

        $signType = '0';

        $post_data = [
            'appid' => $this->appKey,
            'serviceid' => __FUNCTION__,
            'jtnsrsbh' => $this->jtnsrsbh,
            'nsrsbh' => trim($nsrsbh),
            'content' => base64_encode(jsonEncode($content, false)),
            'signature' => $this->signature($content, trim($nsrsbh), __FUNCTION__, $signType),
            'signType' => $signType,
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->send($this->url, $post_data, [], ['enableSSL' => true]);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
    }

    //无盘 添加任务接口（通用提交采集任务报文）
    function addTask(string $nsrsbh, string $province, array $ywBody, string $taskCode = 'A002'): array
    {
        $url = 'task/addTask';

        foreach (self::$province as $work => $py) {
            if (is_numeric(mb_strpos($province, $work))) {
                $province = $py;
                break;
            }
        }

        $post_data = [
            'nsrsbh' => trim($nsrsbh),
            'province' => trim($province),
            'taskCode' => trim($taskCode),
            'ywBody' => $ywBody,
        ];

        CommonService::getInstance()->log4PHP($post_data, 'send', 'jincaijincai');

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($this->wupan_url . $url, $post_data, ['oauthToken' => $this->oauthToken], [], 'postjson');

        CommonService::getInstance()->log4PHP($post_data, 'return', 'jincaijincai');

        return $this->checkResp($res, 'wupan');
    }

    //无盘 查询状态接口
    function obtainResultTraceNo(string $traceNo): array
    {
        $url = 'api/obtainResultTraceNo';

        $post_data = [
            'traceNo' => trim($traceNo)
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($this->wupan_url . $url, $post_data, ['oauthToken' => $this->oauthToken], [], 'postjson');

        return $this->checkResp($res, 'wupan');
    }

    //无盘 取数 主票
    function obtainFpInfo(string $traceNo): array
    {
        $url = 'api/obtainFpInfo';

        $post_data = [
            'traceNo' => trim($traceNo)
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($this->wupan_url . $url, $post_data, ['oauthToken' => $this->oauthToken], [], 'postjson');

        return $this->checkResp($res, 'wupan');
    }

    //无盘 取数 详情
    function obtainFpDetailInfo(string $traceNo): array
    {
        $url = 'api/obtainFpDetailInfo';

        $post_data = [
            'traceNo' => trim($traceNo)
        ];

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(true)
            ->send($this->wupan_url . $url, $post_data, ['oauthToken' => $this->oauthToken], [], 'postjson');

        return $this->checkResp($res, 'wupan');
    }

}


