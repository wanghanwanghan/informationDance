<?php

namespace App\HttpController\Service\LongXin;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use App\Task\Service\TaskService;
use App\Task\TaskList\EntDbTask\insertEnt;
use App\Task\TaskList\EntDbTask\insertFinance;

class LongXinService extends ServiceBase
{
    private $usercode;
    private $userkey;
    private $baseUrl;
    private $sendHeaders;

    public $rangeArr = [
        ['name' => 'F14', 'range' => [-7000, -6500]],
        ['name' => 'F13', 'range' => [-6500, -6000]],
        ['name' => 'F12', 'range' => [-6000, -5500]],
        ['name' => 'F11', 'range' => [-5500, -5000]],
        ['name' => 'F10', 'range' => [-5000, -4500]],
        ['name' => 'F09', 'range' => [-4500, -4000]],
        ['name' => 'F08', 'range' => [-4000, -3500]],
        ['name' => 'F07', 'range' => [-3500, -3000]],
        ['name' => 'F06', 'range' => [-3000, -2500]],
        ['name' => 'F05', 'range' => [-2500, -2000]],
        ['name' => 'F04', 'range' => [-2000, -1500]],
        ['name' => 'F03', 'range' => [-1500, -1000]],
        ['name' => 'F02', 'range' => [-1000, -500]],
        ['name' => 'F01', 'range' => [-500, 0]],
        ['name' => 'Z01', 'range' => [0, 500]],
        ['name' => 'Z02', 'range' => [500, 1000]],
        ['name' => 'Z03', 'range' => [1000, 1500]],
        ['name' => 'Z04', 'range' => [1500, 2000]],
        ['name' => 'Z05', 'range' => [2000, 2500]],
        ['name' => 'Z06', 'range' => [2500, 3000]],
        ['name' => 'Z07', 'range' => [3000, 3500]],
        ['name' => 'Z08', 'range' => [3500, 4000]],
        ['name' => 'Z09', 'range' => [4000, 4500]],
        ['name' => 'Z10', 'range' => [4500, 5000]],
        ['name' => 'Z11', 'range' => [5000, 5500]],
        ['name' => 'Z12', 'range' => [5500, 6000]],
        ['name' => 'Z13', 'range' => [6000, 6500]],
        ['name' => 'Z14', 'range' => [6500, 7000]],
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
    function binaryFind(int $find, int $leftIndex, int $rightIndex): ?array
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

        $res = (new CoHttpClient())->send($this->baseUrl . 'getentid/', $arr, $this->sendHeaders);

        if (!empty($res) && isset($res['data']) && !empty($res['data'])) {
            $entid = $res['data'];
        } else {
            $entid = null;
        }

        return $entid;
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

    //取社保人数
    private function getSocialNum($entId)
    {
        $arr = [
            'entid' => $entId,
            'version' => 'E3',
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())->send($this->baseUrl . 'company_detail/', $arr, $this->sendHeaders);

        if (!empty($res) && isset($res['data']) && !empty($res['data'])) {
            $tmp = $res['data'];
        } else {
            $tmp = null;
        }

        return $tmp;
    }

    //近n年的财务数据
    function getThreeYearsData($postData)
    {
        $entId = $this->getEntid($postData['entName']);

        if (empty($entId)) return ['code' => 102, 'msg' => 'entId是空', 'data' => []];

        TaskService::getInstance()->create(new insertEnt($postData['entName']));

        $ANCHEYEAR = '';
        $temp = [];

        if ($postData['beginYear'] <= 2010) $postData['beginYear'] = 2010;
        if ($postData['beginYear'] >= date('Y')) $postData['beginYear'] = date('Y');

        for ($i = 2010; $i <= $postData['beginYear']; $i++) {
            $ANCHEYEAR .= $i . ',';
            $temp[(string)$i] = null;
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
                $oneYearData['SOCNUM'] = null;
                $temp[$year] = $oneYearData;
            }
            krsort($temp);
        }

        //社保人数数组
        $social = $this->getSocialNum($entId);

        !empty($social) ?: $social = ['AnnualSocial' => []];

        foreach ($social['AnnualSocial'] as $oneSoc) {
            $year = $oneSoc['ANCHEYEAR'];
            if (!is_numeric($year) || !isset($temp[(string)$year])) continue;
            if (isset($oneSoc['so1']) && is_numeric($oneSoc['so1'])) $temp[(string)$year]['SOCNUM'] = $oneSoc['so1'];
        }

        TaskService::getInstance()->create(new insertFinance($postData['entName'], $temp, $social['AnnualSocial']));

        //数字落区间
        foreach ($temp as $year => $arr) {
            foreach ($arr as $field => $val) {
                if ($field === 'SOCNUM' || !is_numeric($val)) continue;
                $temp[$year][$field] = $this->binaryFind($val, 0, count($this->rangeArr) - 1);
            }
        }

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
