<?php

namespace App\HttpController\Service\LongXin;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class LongXinService extends ServiceBase
{
    private $usercode;
    private $userkey;
    private $baseUrl;
    private $sendHeaders;

    public $rangeArr = [
        ['name' => 'A00', 'range' => [0, 10]],
        ['name' => 'A01', 'range' => [10, 15]],
        ['name' => 'A02', 'range' => [15, 20]],
        ['name' => 'A03', 'range' => [20, 25]],
        ['name' => 'A04', 'range' => [25, 30]],
        ['name' => 'A05', 'range' => [30, 35]],
        ['name' => 'A06', 'range' => [35, 40]],
        ['name' => 'A07', 'range' => [40, 45]],
        ['name' => 'A08', 'range' => [45, 50]],
        ['name' => 'A09', 'range' => [50, 55]],
    ];

    function onNewService(): ?bool
    {
        return parent::onNewService();
    }

    function __construct()
    {
        $this->usercode = CreateConf::getInstance()->getConf('longxin.usercode');
        $this->userkey = CreateConf::getInstance()->getConf('longxin.userkey');
        $this->baseUrl = CreateConf::getInstance()->getConf('longxin.baseUrl');

        $this->sendHeaders = [
            'content-type' => 'application/x-www-form-urlencoded',
            'authorization' => '',
        ];

        return parent::__construct();
    }

    //更换区间
    function setRangeArr(array $range): LongXinService
    {
        $this->rangeArr = $range;
        return $this;
    }

    //二分找区间
    function binaryFind(int $find, int $leftIndex = 0, int $rightIndex = 9): ?array
    {
        if (!is_numeric($find)) return null;

        //如果不在所有区间内
        if ($leftIndex > $rightIndex) {
            if ($find < $this->rangeArr[0]['range'][0]) return $this->rangeArr[0];
            if ($find > $this->rangeArr[count($this->rangeArr) - 1]['range'][1])
                return $this->rangeArr[count($this->rangeArr) - 1];
            return null;
        }

        $middle = ($leftIndex + $rightIndex) / 2;

        //如果大于第二个数，肯定在右边
        if ($find > $this->rangeArr[$middle]['range'][1]) {
            return $this->binaryFind($find, $middle + 1, $rightIndex);
        }

        //如果小于第一个数，肯定在左边
        if ($find < $this->rangeArr[$middle]['range'][0])
            return $this->binaryFind($find, $leftIndex, $middle - 1);

        return $this->rangeArr[$middle];
    }

    //创建请求token
    private function createToken($params)
    {
        $str = '';
        ksort($params);

        foreach ($params as $k => $val) {
            $str .= $k . $val;
        }

        return hash_hmac('sha1', $str . $this->usercode, $this->userkey);
    }

    //公司名称换取entid
    private function getEntid($entName): ?string
    {
        $ctype = preg_match('/\d{5}/', $entName) ? 1 : 3;

        $arr = [
            'key' => $entName,
            'ctype' => $ctype,
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())->useCache(false)->send($this->baseUrl . 'getentid/', $arr, $this->sendHeaders);

        if (!empty($res) && isset($res['data']) && !empty($res['data'])) {
            $entid = $res['data'];
        } else {
            $entid = null;
        }

        return $entid;
    }

    //startYear
    private function getStartYear()
    {
        return (int)date('m') >= 9 ? date('Y') - 1 : date('Y') - 2;
    }

    //整理请求结果
    private function checkResp($res)
    {
        $res['Paging'] = null;

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['Result'] = $res['data'];
        $res['Message'] = $res['msg'];

        return $this->createReturn((int)$res['code'], $res['Paging'], $res['Result'], $res['Message']);
    }

    //近n年的财务数据
    function getThreeYearsData($postData)
    {
        $entId = $this->getEntid($postData['entName']);

        if (empty($entId)) return ['code' => 102, 'msg' => 'entId是空', 'data' => []];

        $ANCHEYEAR = '';
        $temp = [];
        for ($i = 9; $i--;) {
            $ANCHEYEAR .= $postData['beginYear'] - $i . ',';
            $key = (string)$postData['beginYear'] - $i;
            $temp[$key] = null;
        }

        $arr = [
            'entid' => $entId,
            'ANCHEYEAR' => trim($ANCHEYEAR, ','),
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())->send($this->baseUrl . 'ar_caiwu/', $arr, $this->sendHeaders);

        if (isset($res['total']) && $res['total'] > 0) {
            foreach ($res['data'] as $oneYearData) {
                $year = (string)trim($oneYearData['ANCHEYEAR']);
                if (!is_numeric($year)) continue;
                $temp[$year] = $oneYearData;
            }
            krsort($temp);
        }


        $arr = [
            'entid' => $entId,
            'version' => 'E3',
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $temp['num'] = (new CoHttpClient())->send($this->baseUrl . 'ar_caiwu/', $arr, $this->sendHeaders);


        return $this->checkRespFlag ?
            $this->checkResp(['code' => 200, 'msg' => '查询成功', 'data' => $temp]) :
            ['code' => 200, 'msg' => '查询成功', 'data' => $temp];
    }

    //对外的最近三年财务数据 只返回一个字段
    function getThreeYearsReturnOneField($postData, $field)
    {
        $entId = $this->getEntid($postData['entName']);

        if (empty($entId)) return ['code' => 102, 'msg' => 'entId是空', 'data' => []];

        $yearStart = $this->getStartYear();

        $return = [];

        for ($i = 3; $i--;) {
            $arr = [
                'entid' => $entId,
                'year' => $yearStart - $i,
                'type' => 2,
                'usercode' => $this->usercode
            ];

            $this->sendHeaders['authorization'] = $this->createToken($arr);

            $res = (new CoHttpClient())->send($this->baseUrl . 'xindong/search/', $arr, $this->sendHeaders);

            if (isset($res['data']) && !empty($res['data'])) {
                isset($res['data'][$field]) ? $temp = trim($res['data'][$field]) : $temp = '';
                $return[$yearStart - $i] = $temp;
            } else {
                $return[$yearStart - $i] = '';
            }
        }

        krsort($return);

        return $this->checkRespFlag ?
            $this->checkResp(['code' => 200, 'msg' => '查询成功', 'data' => $return]) :
            ['code' => 200, 'msg' => '查询成功', 'data' => $return];
    }


    public function test()
    {
        return $this->getThreeYearsData(['entName' => '北京每日信动科技有限公司']);
    }

}
