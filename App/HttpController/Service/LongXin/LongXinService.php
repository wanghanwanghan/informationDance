<?php

namespace App\HttpController\Service\LongXin;

use App\HttpController\Models\EntDb\EntDbEnt;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\ServiceBase;
use App\HttpController\Service\TaoShu\TaoShuService;
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
        [
            'ASSGRO',//0资产总额
            'LIAGRO',//1负债总额
            'VENDINC',//2营业总收入
            'MAIBUSINC',//3主营业务收入
            'PROGRO',//4利润总额
            'NETINC',//5净利润
            'RATGRO',//6纳税总额
            'TOTEQU',//7所有者权益
            'C_ASSGROL',//9净资产
            'A_ASSGROL',//10平均资产总额
            'CA_ASSGRO',//11平均净资产
            'A_VENDINCL',//15企业人均产值
            'A_PROGROL',//16企业人均盈利
        ],
        [
            ['name' => 'F64', 'range' => []],
            ['name' => 'F63', 'range' => []],
            ['name' => 'F62', 'range' => []],
            ['name' => 'F61', 'range' => []],
            ['name' => 'F60', 'range' => []],
            ['name' => 'F59', 'range' => []],
            ['name' => 'F58', 'range' => []],
            ['name' => 'F57', 'range' => []],
            ['name' => 'F56', 'range' => []],
            ['name' => 'F55', 'range' => []],
            ['name' => 'F54', 'range' => []],
            ['name' => 'F53', 'range' => []],
            ['name' => 'F52', 'range' => []],
            ['name' => 'F51', 'range' => []],
            ['name' => 'F50', 'range' => []],
            ['name' => 'F49', 'range' => []],
            ['name' => 'F48', 'range' => []],
            ['name' => 'F47', 'range' => []],
            ['name' => 'F46', 'range' => []],
            ['name' => 'F45', 'range' => []],
            ['name' => 'F44', 'range' => []],
            ['name' => 'F43', 'range' => []],
            ['name' => 'F42', 'range' => []],
            ['name' => 'F41', 'range' => []],
            ['name' => 'F40', 'range' => []],
            ['name' => 'F39', 'range' => []],
            ['name' => 'F38', 'range' => []],
            ['name' => 'F37', 'range' => []],
            ['name' => 'F36', 'range' => []],
            ['name' => 'F35', 'range' => []],
            ['name' => 'F34', 'range' => []],
            ['name' => 'F33', 'range' => []],
            ['name' => 'F32', 'range' => []],
            ['name' => 'F31', 'range' => []],
            ['name' => 'F30', 'range' => []],
            ['name' => 'F29', 'range' => []],
            ['name' => 'F28', 'range' => []],
            ['name' => 'F27', 'range' => []],
            ['name' => 'F26', 'range' => []],
            ['name' => 'F25', 'range' => []],
            ['name' => 'F24', 'range' => []],
            ['name' => 'F23', 'range' => []],
            ['name' => 'F22', 'range' => []],
            ['name' => 'F21', 'range' => []],
            ['name' => 'F20', 'range' => []],
            ['name' => 'F19', 'range' => []],
            ['name' => 'F18', 'range' => []],
            ['name' => 'F17', 'range' => []],
            ['name' => 'F16', 'range' => []],
            ['name' => 'F15', 'range' => []],
            ['name' => 'F14', 'range' => []],
            ['name' => 'F13', 'range' => []],
            ['name' => 'F12', 'range' => []],
            ['name' => 'F11', 'range' => []],
            ['name' => 'F10', 'range' => []],
            ['name' => 'F09', 'range' => []],
            ['name' => 'F08', 'range' => []],
            ['name' => 'F07', 'range' => []],
            ['name' => 'F06', 'range' => []],
            ['name' => 'F05', 'range' => []],
            ['name' => 'F04', 'range' => []],
            ['name' => 'F03', 'range' => []],
            ['name' => 'F02', 'range' => []],
            ['name' => 'F01', 'range' => []],
            ['name' => 'Z00', 'range' => [0, 0]],
            ['name' => 'Z01', 'range' => [1, 10]],
            ['name' => 'Z02', 'range' => [10,20]],
            ['name' => 'Z03', 'range' => [20,40]],
            ['name' => 'Z04', 'range' => [40,60]],
            ['name' => 'Z05', 'range' => [60,80]],
            ['name' => 'Z06', 'range' => [80,100]],
            ['name' => 'Z07', 'range' => [100,120]],
            ['name' => 'Z08', 'range' => [120,140]],
            ['name' => 'Z09', 'range' => [140,160]],
            ['name' => 'Z10', 'range' => [160,180]],
            ['name' => 'Z11', 'range' => [180,200]],
            ['name' => 'Z12', 'range' => [200,220]],
            ['name' => 'Z13', 'range' => [220,240]],
            ['name' => 'Z14', 'range' => [240,270]],
            ['name' => 'Z15', 'range' => [270,320]],
            ['name' => 'Z16', 'range' => [320,380]],
            ['name' => 'Z17', 'range' => [380,460]],
            ['name' => 'Z18', 'range' => [460,550]],
            ['name' => 'Z19', 'range' => [550,660]],
            ['name' => 'Z20', 'range' => [660,790]],
            ['name' => 'Z21', 'range' => [790,950]],
            ['name' => 'Z22', 'range' => [950,1100]],
            ['name' => 'Z23', 'range' => [1100,1400]],
            ['name' => 'Z24', 'range' => [1400,1600]],
            ['name' => 'Z25', 'range' => [1600,2000]],
            ['name' => 'Z26', 'range' => [2000,2400]],
            ['name' => 'Z27', 'range' => [2400,2800]],
            ['name' => 'Z28', 'range' => [2800,3400]],
            ['name' => 'Z29', 'range' => [3400,4100]],
            ['name' => 'Z30', 'range' => [4100,4900]],
            ['name' => 'Z31', 'range' => [4900,5900]],
            ['name' => 'Z32', 'range' => [5900,7100]],
            ['name' => 'Z33', 'range' => [7100,8500]],
            ['name' => 'Z34', 'range' => [8500,10000]],
            ['name' => 'Z35', 'range' => [10000,12000]],
            ['name' => 'Z36', 'range' => [12000,15000]],
            ['name' => 'Z37', 'range' => [15000,18000]],
            ['name' => 'Z38', 'range' => [18000,21000]],
            ['name' => 'Z39', 'range' => [21000,25000]],
            ['name' => 'Z40', 'range' => [25000,30000]],
            ['name' => 'Z41', 'range' => [30000,37000]],
            ['name' => 'Z42', 'range' => [37000,44000]],
            ['name' => 'Z43', 'range' => [44000,53000]],
            ['name' => 'Z44', 'range' => [53000,63000]],
            ['name' => 'Z45', 'range' => [63000,76000]],
            ['name' => 'Z46', 'range' => [76000,91000]],
            ['name' => 'Z47', 'range' => [91000,110000]],
            ['name' => 'Z48', 'range' => [110000,130000]],
            ['name' => 'Z49', 'range' => [130000,160000]],
            ['name' => 'Z50', 'range' => [160000,190000]],
            ['name' => 'Z51', 'range' => [190000,230000]],
            ['name' => 'Z52', 'range' => [230000,270000]],
            ['name' => 'Z53', 'range' => [270000,330000]],
            ['name' => 'Z54', 'range' => [330000,390000]],
            ['name' => 'Z55', 'range' => [390000,470000]],
            ['name' => 'Z56', 'range' => [470000,560000]],
            ['name' => 'Z57', 'range' => [560000,680000]],
            ['name' => 'Z58', 'range' => [680000,810000]],
            ['name' => 'Z59', 'range' => [810000,970000]],
            ['name' => 'Z60', 'range' => [970000,1200000]],
            ['name' => 'Z61', 'range' => [1200000,1400000]],
            ['name' => 'Z62', 'range' => [1400000,1700000]],
            ['name' => 'Z63', 'range' => [1700000,2000000]],
            ['name' => 'Z64', 'range' => [2000000,2400000]],
            ['name' => 'Z65', 'range' => [2400000,2900000]],
            ['name' => 'Z66', 'range' => [2900000,3500000]],
            ['name' => 'Z67', 'range' => [3500000,4200000]],
            ['name' => 'Z68', 'range' => [4200000,5000000]],
            ['name' => 'Z69', 'range' => [5000000,6000000]],
            ['name' => 'Z70', 'range' => [6000000,7200000]],
            ['name' => 'Z71', 'range' => [7200000,8700000]],
            ['name' => 'Z72', 'range' => [8700000,10000000]],
            ['name' => 'Z73', 'range' => [10000000,13000000]],
            ['name' => 'Z74', 'range' => [13000000,15000000]],
            ['name' => 'Z75', 'range' => [15000000,18000000]],
            ['name' => 'Z76', 'range' => [18000000,22000000]],
            ['name' => 'Z77', 'range' => [22000000,26000000]],
            ['name' => 'Z78', 'range' => [26000000,31000000]],
            ['name' => 'Z79', 'range' => [31000000,37000000]],
            ['name' => 'Z80', 'range' => [37000000,45000000]],
            ['name' => 'Z81', 'range' => [45000000,54000000]],
            ['name' => 'Z82', 'range' => [54000000,65000000]],
            ['name' => 'Z83', 'range' => [65000000,78000000]],
            ['name' => 'Z84', 'range' => [78000000,93000000]],
            ['name' => 'Z85', 'range' => [93000000,93000000]],
        ]
    ];

    public $rangeArrRatio = [
        [
            'C_INTRATESL',//12净利率
            'ATOL',//13资产周转率
            'ASSGRO_C_INTRATESL',//14总资产净利率
            'ROAL',//17总资产回报率 ROA
            'ROE_AL',//18净资产回报率 ROE (A)
            'ROE_BL',//19净资产回报率 ROE (B)
            'DEBTL',//20资产负债率
            'MAIBUSINC_RATIOL',//22主营业务比率
            'NALR',//23净资产负债率
            'OPM',//24营业利润率
            'ROCA',//25资本保值增值率
            'NOR',//26营业净利率
            'PMOTA',//27总资产利润率
            'TBR',//28税收负担率
            'ASSGRO_yoy',//30资产总额同比
            'LIAGRO_yoy',//31负债总额同比
            'VENDINC_yoy',//32营业总收入同比
            'MAIBUSINC_yoy',//33主营业务收入同比
            'PROGRO_yoy',//34利润总额同比
            'NETINC_yoy',//35净利润同比
            'RATGRO_yoy',//36纳税总额同比
            'TOTEQU_yoy',//37所有者权益同比
            'TBR_new',//38税收负担率
        ],
        [
            ['name' => 'F13', 'range' => [-40.96, -20.48]],
            ['name' => 'F12', 'range' => [-20.48, -10.24]],
            ['name' => 'F11', 'range' => [-10.24, -5.12]],
            ['name' => 'F10', 'range' => [-5.12, -2.56]],
            ['name' => 'F09', 'range' => [-2.56, -1.28]],
            ['name' => 'F08', 'range' => [-1.28, -0.64]],
            ['name' => 'F07', 'range' => [-0.64, -0.32]],
            ['name' => 'F06', 'range' => [-0.32, -0.16]],
            ['name' => 'F05', 'range' => [-0.16, -0.08]],
            ['name' => 'F04', 'range' => [-0.08, -0.04]],
            ['name' => 'F03', 'range' => [-0.04, -0.02]],
            ['name' => 'F02', 'range' => [-0.02, -0.01]],
            ['name' => 'F01', 'range' => [-0.01, 0]],
            ['name' => 'Z01', 'range' => [0, 0.01]],
            ['name' => 'Z02', 'range' => [0.01, 0.02]],
            ['name' => 'Z03', 'range' => [0.02, 0.04]],
            ['name' => 'Z04', 'range' => [0.04, 0.08]],
            ['name' => 'Z05', 'range' => [0.08, 0.16]],
            ['name' => 'Z06', 'range' => [0.16, 0.32]],
            ['name' => 'Z07', 'range' => [0.32, 0.64]],
            ['name' => 'Z08', 'range' => [0.64, 1.28]],
            ['name' => 'Z09', 'range' => [1.28, 2.56]],
            ['name' => 'Z10', 'range' => [2.56, 5.12]],
            ['name' => 'Z11', 'range' => [5.12, 10.24]],
            ['name' => 'Z12', 'range' => [10.24, 20.48]],
            ['name' => 'Z13', 'range' => [20.48, 40.96]],
        ]
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
    function setRangeArr(array $range, array $ratio): LongXinService
    {
        if (!empty($range)) $this->rangeArr = $range;
        if (!empty($ratio)) $this->rangeArrRatio = $ratio;

        return $this;
    }

    //二分找区间
    function binaryFind(int $find, int $leftIndex, int $rightIndex, array $range): ?array
    {
        if (!is_numeric($find)) return null;

        //如果不在所有区间内
        if ($leftIndex > $rightIndex) {
            if ($find < $range[0]['range'][0]) return $range[0];
            if ($find > $range[count($range) - 1]['range'][1])
                return $range[count($range) - 1];
            return null;
        }

        $middle = ($leftIndex + $rightIndex) / 2;

        //如果大于第二个数，肯定在右边
        if ($find > $range[$middle]['range'][1]) {
            return $this->binaryFind($find, $middle + 1, $rightIndex, $range);
        }

        //如果小于第一个数，肯定在左边
        if ($find < $range[$middle]['range'][0])
            return $this->binaryFind($find, $leftIndex, $middle - 1, $range);

        return $range[$middle];
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

        $res = (new CoHttpClient())->useCache(false)
            ->send($this->baseUrl . 'getentid/', $arr, $this->sendHeaders);

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

    //是否已经入库
    private function alreadyInserted($postData): array
    {
        $entName = $postData['entName'];
        $code = $postData['code'];
        $beginYear = $postData['beginYear'];

        try {
            $entInfo = EntDbEnt::create()->where(['name' => $entName])->get();
        } catch (\Throwable $e) {

        }

        return [];
    }

    //近n年的财务数据
    function getFinanceData($postData, $toRange = true)
    {
        $check = $this->alreadyInserted($postData);

        $entId = $this->getEntid($postData['entName']);

        if (empty($entId)) return ['code' => 102, 'msg' => 'entId是空', 'data' => []];

        TaskService::getInstance()->create(new insertEnt($postData['entName'], $postData['code']));

        $ANCHEYEAR = '';
        $temp = [];
        for ($i = 2010; $i <= date('Y'); $i++) {
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
                $year = trim($oneYearData['ANCHEYEAR']) . '';
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

        //原值计算
        if ($postData['dataCount'] > 1) {
            $temp = $this->exprHandle($temp);
        }

        //取哪年的数据
        $readyReturn = [];
        for ($i = $postData['dataCount']; $i--;) {
            $tmp = $postData['beginYear'] - $i;
            $tmp = (string)$tmp;
            isset($temp[$tmp]) ? $readyReturn[$tmp] = $temp[$tmp] : $readyReturn[$tmp] = null;
        }

        //数字落区间
        if ($toRange) {
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (in_array($field, $this->rangeArr[0]) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $this->binaryFind($val, 0, count($this->rangeArr[1]) - 1, $this->rangeArr[1]);
                    } elseif (in_array($field, $this->rangeArrRatio[0]) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $this->binaryFind($val, 0, count($this->rangeArrRatio[1]) - 1, $this->rangeArrRatio[1]);
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
        }

        krsort($readyReturn);

        return $this->checkRespFlag ?
            $this->checkResp(['code' => 200, 'msg' => '查询成功', 'data' => $readyReturn]) :
            ['code' => 200, 'msg' => '查询成功', 'data' => $readyReturn];
    }

    //近n年的财务数据 含 并表
    function getFinanceBaseMergeData($postData, $toRange = true)
    {
        $check = $this->alreadyInserted($postData);

        $entId = $this->getEntid($postData['entName']);

        if (empty($entId)) return ['code' => 102, 'msg' => 'entId是空', 'data' => []];

        $toReturn = [
            [
                'entName' => $postData['entName'],
                'entId' => $entId,
            ]
        ];

        //分支机构
        $getBranchInfo = (new TaoShuService())->post([
            'entName' => $postData['entName'],
            'pageNo' => 1,
            'pageSize' => 100,
        ], 'getBranchInfo');

        empty($getBranchInfo['RESULTDATA']) ?
            $getBranchInfo = [] :
            $getBranchInfo = $getBranchInfo['RESULTDATA'];

        //对外投资
        $getInvestmentAbroadInfo = (new TaoShuService())->post([
            'entName' => $postData['entName'],
            'pageNo' => 1,
            'pageSize' => 100,
        ], 'getInvestmentAbroadInfo');

        empty($getInvestmentAbroadInfo['RESULTDATA']) ?
            $getInvestmentAbroadInfo = [] :
            $getInvestmentAbroadInfo = $getInvestmentAbroadInfo['RESULTDATA'];

        $i = 1;

        if (!empty($getBranchInfo)) {
            foreach ($getBranchInfo as $one) {
                if ($i > 5) continue;
                TaskService::getInstance()->create(new insertEnt($one['ENTNAME'], $one['SHXYDM']));
                $entId = $this->getEntid($one['ENTNAME']);
                $toReturn[] = [
                    'entName' => $one['ENTNAME'],
                    'entId' => $entId,
                ];
                $i++;
            }
        }

        $i = 1;

        if (!empty($getInvestmentAbroadInfo)) {
            foreach ($getInvestmentAbroadInfo as $one) {
                if ($i > 5) continue;
                TaskService::getInstance()->create(new insertEnt($one['ENTNAME'], $one['SHXYDM']));
                $entId = $this->getEntid($one['ENTNAME']);
                $toReturn[] = [
                    'entName' => $one['ENTNAME'],
                    'entId' => $entId,
                ];
                $i++;
            }
        }

        $ANCHEYEAR = '';
        for ($i = 2010; $i <= date('Y'); $i++) {
            $ANCHEYEAR .= $i . ',';
        }
        $ANCHEYEAR = trim($ANCHEYEAR, ',');

        foreach ($toReturn as $key => $oneTargetEnt) {
            if (empty($oneTargetEnt['entId'])) {
                $toReturn[$key]['result'] = null;
                continue;
            }
            $arr = [
                'entid' => $oneTargetEnt['entId'],
                'ANCHEYEAR' => $ANCHEYEAR,
                'usercode' => $this->usercode
            ];
            $this->sendHeaders['authorization'] = $this->createToken($arr);
            $res = (new CoHttpClient())->send($this->baseUrl . 'ar_caiwu/', $arr, $this->sendHeaders);
            if (isset($res['total']) && $res['total'] > 0) {
                foreach ($res['data'] as $oneYearData) {
                    $year = trim($oneYearData['ANCHEYEAR']) . '';
                    if (!is_numeric($year)) continue;
                    $oneYearData['SOCNUM'] = null;
                    $toReturn[$key]['result'][$year] = $oneYearData;
                }
            } else {
                $toReturn[$key]['result'] = null;
            }
        }

        //社保人数
        foreach ($toReturn as $key => $oneTargetEnt) {
            if (empty($oneTargetEnt['result'])) continue;
            $social = $this->getSocialNum($oneTargetEnt['entId']);
            !empty($social) ?: $social = ['AnnualSocial' => []];
            foreach ($social['AnnualSocial'] as $oneSoc) {
                $year = $oneSoc['ANCHEYEAR'] . '';
                if (!is_numeric($year) || !isset($oneTargetEnt['result'][$year])) continue;
                if (isset($oneSoc['so1']) && is_numeric($oneSoc['so1'])) {
                    $toReturn[$key]['result'][$year]['SOCNUM'] = $oneSoc['so1'];
                }
            }
        }

        //取哪几年的
        $temp = [];
        for ($i = $postData['dataCount'] + 1; $i--;) {
            $tmpYear = ($postData['beginYear'] - $i) . '';
            $temp[$tmpYear] = [
                'VENDINC' => null,
                'ASSGRO' => null,
                'ANCHEYEAR' => $tmpYear,
                'MAIBUSINC' => null,
                'TOTEQU' => null,
                'RATGRO' => null,
                'PROGRO' => null,
                'NETINC' => null,
                'LIAGRO' => null,
                'SOCNUM' => null,
            ];
            //并表
            foreach ($toReturn as $oneTargetEnt) {
                if (isset($oneTargetEnt['result'][$tmpYear])) {
                    $temp[$tmpYear] = [
                        'VENDINC' => $temp[$tmpYear]['VENDINC'] + $oneTargetEnt['result'][$tmpYear]['VENDINC'],
                        'ASSGRO' => $temp[$tmpYear]['ASSGRO'] + $oneTargetEnt['result'][$tmpYear]['ASSGRO'],
                        'MAIBUSINC' => $temp[$tmpYear]['MAIBUSINC'] + $oneTargetEnt['result'][$tmpYear]['MAIBUSINC'],
                        'TOTEQU' => $temp[$tmpYear]['TOTEQU'] + $oneTargetEnt['result'][$tmpYear]['TOTEQU'],
                        'RATGRO' => $temp[$tmpYear]['RATGRO'] + $oneTargetEnt['result'][$tmpYear]['RATGRO'],
                        'PROGRO' => $temp[$tmpYear]['PROGRO'] + $oneTargetEnt['result'][$tmpYear]['PROGRO'],
                        'NETINC' => $temp[$tmpYear]['NETINC'] + $oneTargetEnt['result'][$tmpYear]['NETINC'],
                        'LIAGRO' => $temp[$tmpYear]['LIAGRO'] + $oneTargetEnt['result'][$tmpYear]['LIAGRO'],
                        'SOCNUM' => $temp[$tmpYear]['SOCNUM'] + $oneTargetEnt['result'][$tmpYear]['SOCNUM'],
                    ];
                }
            }
        }

        //原值计算
        if ($postData['dataCount'] > 1) {
            $temp = $this->exprHandle($temp);
        }

        array_pop($temp);

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

    //原值计算
    function exprHandle($origin)
    {
        //0资产总额 ASSGRO
        //1负债总额 LIAGRO
        //2营业总收入 VENDINC
        //3主营业务收入 MAIBUSINC
        //4利润总额 PROGRO
        //5净利润 NETINC
        //6纳税总额 RATGRO
        //7所有者权益 TOTEQU
        //8社保人数 SOCNUM

        //9净资产 C_ASSGROL
        //10平均资产总额 A_ASSGROL
        //11平均净资产 CA_ASSGRO
        //12净利率 C_INTRATESL
        //13资产周转率 ATOL
        //14总资产净利率 ASSGRO_C_INTRATESL
        //15企业人均产值 A_VENDINCL
        //16企业人均盈利 A_PROGROL
        //17总资产回报率 ROA ROAL
        //18净资产回报率 ROE (A) ROE_AL
        //19净资产回报率 ROE (B) ROE_BL
        //20资产负债率 DEBTL
        //21权益乘数 EQUITYL
        //22主营业务比率 MAIBUSINC_RATIOL

        //23净资产负债率 NALR
        //24营业利润率 OPM
        //25资本保值增值率 ROCA
        //26营业净利率 NOR
        //27总资产利润率 PMOTA
        //28税收负担率 TBR
        //29权益乘数 EQUITYL_new

        //30资产总额同比 ASSGRO_yoy
        //31负债总额同比 LIAGRO_yoy
        //32营业总收入同比 VENDINC_yoy
        //33主营业务收入同比 MAIBUSINC_yoy
        //34利润总额同比 PROGRO_yoy
        //35净利润同比 NETINC_yoy
        //36纳税总额同比 RATGRO_yoy
        //37所有者权益同比 TOTEQU_yoy

        //38税收负担率 TBR_new

        $now = [];
        foreach ($origin as $year => $arr) {
            $now[$year] = [
                $arr['ASSGRO'],
                $arr['LIAGRO'],
                $arr['VENDINC'],
                $arr['MAIBUSINC'],
                $arr['PROGRO'],
                $arr['NETINC'],
                $arr['RATGRO'],
                $arr['TOTEQU'],
                $arr['SOCNUM'],
            ];
        }
        $origin = $now;

        $keys = array_keys($origin);

        $max = max($keys);
        $min = min($keys);

        for ($i = $min; $i <= $max; $i++) {
            if ((isset($origin[$i]) && empty($origin[$i])) || !isset($origin[$i])) {
                $origin[$i] = [null, null, null, null, null, null, null, null, null];
            }
        }

        ksort($origin);

        //恢复数组key
        $res = $this->expr($origin);

        //返回字段模版
        $model = [
            'ASSGRO' => null,
            'LIAGRO' => null,
            'VENDINC' => null,
            'MAIBUSINC' => null,
            'PROGRO' => null,
            'NETINC' => null,
            'RATGRO' => null,
            'TOTEQU' => null,
            'SOCNUM' => null,
            'C_ASSGROL' => null,
            'A_ASSGROL' => null,
            'CA_ASSGRO' => null,
            'C_INTRATESL' => null,
            'ATOL' => null,
            'ASSGRO_C_INTRATESL' => null,
            'A_VENDINCL' => null,
            'A_PROGROL' => null,
            'ROAL' => null,
            'ROE_AL' => null,
            'ROE_BL' => null,
            'DEBTL' => null,
            'EQUITYL' => null,
            'MAIBUSINC_RATIOL' => null,
            'NALR' => null,
            'OPM' => null,
            'ROCA' => null,
            'NOR' => null,
            'PMOTA' => null,
            'TBR' => null,
            'EQUITYL_new' => null,
            'ASSGRO_yoy' => null,
            'LIAGRO_yoy' => null,
            'VENDINC_yoy' => null,
            'MAIBUSINC_yoy' => null,
            'PROGRO_yoy' => null,
            'NETINC_yoy' => null,
            'RATGRO_yoy' => null,
            'TOTEQU_yoy' => null,
            'TBR_new' => null,
        ];

        $retrun = [];

        foreach ($res as $year => $arr) {
            $retrun[$year] = array_combine(array_keys($model), array_values($arr));
        }

        return $retrun;
    }




















    //================================================================================================================//
    //以下是计算的过程，放到service的最后面
    private function expr($origin)
    {
        //0资产总额
        //1负债总额
        //2营业总收入
        //3主营业务收入
        //4利润总额
        //5净利润
        //6纳税总额
        //7所有者权益
        //8社保人数
        //9净资产
        $origin = $this->jzc($origin);
        //10⚠️平均资产总额
        $origin = $this->pjzcze($origin);
        //11⚠️平均净资产
        $origin = $this->pjjzc($origin);
        //12净利率
        $origin = $this->jll($origin);
        //13资产周转率
        $origin = $this->zczzl($origin);
        //14总资产净利率
        $origin = $this->zzcjll($origin);
        //15企业人均产值
        $origin = $this->qyrjcz($origin);
        //16企业人均盈利
        $origin = $this->qyrjyl($origin);
        //17总资产回报率
        $origin = $this->roa($origin);
        //18净资产回报率
        $origin = $this->roe_a($origin);
        //19净资产回报率
        $origin = $this->roe_b($origin);
        //20资产负债率
        $origin = $this->zcfzl($origin);
        //21权益乘数
        $origin = $this->qycs($origin);
        //22主营业务比率
        $origin = $this->zyywbl($origin);
        //23净资产负债率
        $origin = $this->jzcfzl($origin);
        //24营业利润率
        $origin = $this->yylrl($origin);
        //25资本保值增值率
        $origin = $this->zbbzzzl($origin);
        //26营业净利率
        $origin = $this->zyjll($origin);
        //27总资产利润率
        $origin = $this->zzclrl($origin);
        //28税收负担率
        $origin = $this->ssfdl($origin);
        //29权益乘数
        $origin = $this->qycs_new($origin);
        //30资产总额同比 ASSGRO_yoy
        $origin = $this->zcze_yoy($origin);
        //31负债总额同比 LIAGRO_yoy
        $origin = $this->fzze_yoy($origin);
        //32营业总收入同比 VENDINC_yoy
        $origin = $this->yyzsr_yoy($origin);
        //33主营业务收入同比 MAIBUSINC_yoy
        $origin = $this->zyywsr_yoy($origin);
        //34利润总额同比 PROGRO_yoy
        $origin = $this->lrze_yoy($origin);
        //35净利润同比 NETINC_yoy
        $origin = $this->jlr_yoy($origin);
        //36纳税总额同比 RATGRO_yoy
        $origin = $this->nsze_yoy($origin);
        //37所有者权益同比 TOTEQU_yoy
        $origin = $this->syzqy_yoy($origin);
        //38税收负担率 new
        $origin = $this->ssfdl_new($origin);
        krsort($origin);

        return $origin;
    }

    //9净资产 0资产总额 - 1负债总额
    private function jzc($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[0]) && is_numeric($val[1])) {
                $value = $val[0] - $val[1];
            } else {
                $value = null;
            }
            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //10⚠️平均资产总额 (0去年资产总额 + 0当年资产总额) / 2
    private function pjzcze($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][0])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][0])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][0];
            $now = $origin[$year][0];

            array_push($origin[$year], ($last + $now) / 2);
        }

        return $origin;
    }

    //11⚠️平均净资产 (9去年净资产 + 9当年净资产) / 2
    private function pjjzc($origin)
    {
        foreach ($origin as $year => $val) {
            //去年没数据
            if (!isset($origin[$year - 1]) || !is_numeric($origin[$year - 1][9])) {
                array_push($origin[$year], null);
                continue;
            }

            //今年没数据
            if (!is_numeric($origin[$year][9])) {
                array_push($origin[$year], null);
                continue;
            }

            $value = ($origin[$year - 1][9] + $origin[$year][9]) / 2;

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //12净利率 5净利润 / 3主营业务收入
    private function jll($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[3]) && $val[3] !== 0) {
                $value = $val[5] / $val[3];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //13资产周转率 2营业总收入 / 10平均资产总额
    private function zczzl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[2]) && is_numeric($val[10]) && $val[10] !== 0) {
                $value = $val[2] / $val[10];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //14总资产净利率 12净利率 * 13资产周转率
    private function zzcjll($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[12]) && is_numeric($val[13])) {
                $value = $val[12] * $val[13];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //15企业人均产值 3主营业务收入 / 8缴纳社保人数
    private function qyrjcz($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[3]) && is_numeric($val[8]) && $val[8] !== 0) {
                $value = $val[3] / $val[8];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //16企业人均盈利 5净利润 / 8缴纳社保人数
    private function qyrjyl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[8]) && $val[8] !== 0) {
                $value = $val[5] / $val[8];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //17总资产回报率 ROA 5净利润 / 10平均资产总额
    private function roa($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[10]) && $val[10] !== 0) {
                $value = $val[5] / $val[10];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //18净资产回报率 ROE (A) 5净利润 / 11平均净资产总额
    private function roe_a($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[11]) && $val[11] !== 0) {
                $value = $val[5] / $val[11];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //19净资产回报率 ROE (B) 5净利润 / 7年度所有者权益
    private function roe_b($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[7]) && $val[7] !== 0) {
                $value = $val[5] / $val[7];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //20资产负债率 1负债总额 / 0资产总额
    private function zcfzl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[1]) && is_numeric($val[0]) && $val[0] !== 0) {
                $value = $val[1] / $val[0];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //21权益乘数 1 / (1 - 资产负债率)
    private function qycs($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[20]) && $val[20] !== 1) {
                $value = 1 / (1 - $val[20]);
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //22主营业务比率 3主营业务收入 / 2营业总收入
    private function zyywbl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[3]) && is_numeric($val[2]) && $val[2] !== 0) {
                $value = $val[3] / $val[2];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 23净资产负债率 1负债总额 / 7所有者权益
    private function jzcfzl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[1]) && is_numeric($val[7]) && $val[7] !== 0) {
                $value = $val[1] / $val[7];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 24营业利润率 4利润总额 / 2营业总收入
    private function yylrl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[4]) && is_numeric($val[2]) && $val[2] !== 0) {
                $value = $val[4] / $val[2];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 25资本保值增值率 (7去年所有者权益 + 5当年净利润)/ 7去年所有者权益
    private function zbbzzzl($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][7])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][5]) || $origin[$lastYear][7] === 0) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last7 = $origin[$lastYear][7];
            $now5 = $origin[$year][5];

            array_push($origin[$year], ($last7 + $now5) / $last7);
        }

        return $origin;
    }

    //new 26营业净利率 5净利润 / 2营业总收入
    private function zyjll($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[5]) && is_numeric($val[2]) && $val[2] !== 0) {
                $value = $val[5] / $val[2];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 27总资产利润率 4利润总额 / 10平均资产总额
    private function zzclrl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[4]) && is_numeric($val[10]) && $val[10] !== 0) {
                $value = $val[4] / $val[10];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 28税收负担率 6纳税总额 / 2营业总收入
    private function ssfdl($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[6]) && is_numeric($val[2]) && $val[2] !== 0) {
                $value = $val[6] / $val[2];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //new 29权益乘数 0资产总额 / 7所有者权益
    private function qycs_new($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[0]) && is_numeric($val[7]) && $val[7] !== 0) {
                $value = $val[0] / $val[7];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }

    //30资产总额同比 ASSGRO_yoy
    private function zcze_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][0])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][0])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][0];
            $now = $origin[$year][0];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //31负债总额同比 LIAGRO_yoy
    private function fzze_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][1])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][1])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][1];
            $now = $origin[$year][1];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //32营业总收入同比 VENDINC_yoy
    private function yyzsr_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][2])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][2])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][2];
            $now = $origin[$year][2];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //33主营业务收入同比 MAIBUSINC_yoy
    private function zyywsr_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][3])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][3])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][3];
            $now = $origin[$year][3];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //34利润总额同比 PROGRO_yoy
    private function lrze_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][4])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][4])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][4];
            $now = $origin[$year][4];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //35净利润同比 NETINC_yoy
    private function jlr_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][5])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][5])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][5];
            $now = $origin[$year][5];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //36纳税总额同比 RATGRO_yoy
    private function nsze_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][6])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][6])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][6];
            $now = $origin[$year][6];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //37所有者权益同比 TOTEQU_yoy
    private function syzqy_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][7])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][7])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][7];
            $now = $origin[$year][7];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }

    //new 38税收负担率 6纳税总额 - 4利润总额 * 0.2 / 2营业总收入
    private function ssfdl_new($origin)
    {
        foreach ($origin as $year => $val) {
            if (is_numeric($val[6]) && is_numeric($val[4]) && is_numeric($val[2]) && $val[2] !== 0) {
                $value = ($val[6] - $val[4] * 0.2) / $val[2];
            } else {
                $value = null;
            }

            array_push($origin[$year], $value);
        }

        return $origin;
    }


}
