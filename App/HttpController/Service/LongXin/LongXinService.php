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

    public $cal = true;
    public $rangeArr = [];
    public $rangeArrRatio = [];

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
        $this->rangeArr = $range;
        $this->rangeArrRatio = $ratio;

        return $this;
    }

    function setCal(bool $type): LongXinService
    {
        $this->cal = $type;

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

        if (isset($res['total'])) {
            $res['Paging']['total'] = $res['total'] - 0;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['Result'] = $res['data'];
        $res['Message'] = $res['msg'] ?? '';

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

    //超级搜索
    function superSearch($postData)
    {
        $arr = [
            'usercode' => $this->usercode
        ];

        $arr = array_merge($arr, $postData);

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())->send($this->baseUrl . 'api/super_search/', $arr, $this->sendHeaders);

        return $this->checkRespFlag ? $this->checkResp($res) : $res;
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
            $temp[$i . ''] = null;
        }
        $arr = [
            'entid' => $entId,
            'ANCHEYEAR' => trim($ANCHEYEAR, ','),
            'usercode' => $this->usercode
        ];

        $this->sendHeaders['authorization'] = $this->createToken($arr);

        $res = (new CoHttpClient())
            ->useCache(true)
            ->send($this->baseUrl . 'ar_caiwu/', $arr, $this->sendHeaders);

        if (isset($res['total']) && $res['total'] > 0) {
            foreach ($res['data'] as $oneYearData) {
                $year = trim($oneYearData['ANCHEYEAR']) . '';
                if (!is_numeric($year)) continue;
                $oneYearData['SOCNUM'] = null;
                unset($oneYearData['TEL']);
                unset($oneYearData['BUSST']);
                unset($oneYearData['DOM']);
                unset($oneYearData['EMAIL']);
                unset($oneYearData['POSTALCODE']);
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
            if (isset($oneSoc['so1']) && is_numeric($oneSoc['so1'])) {
                $temp[(string)$year]['SOCNUM'] = $oneSoc['so1'];
            }
        }

        TaskService::getInstance()->create(new insertFinance($postData['entName'], $temp, $social['AnnualSocial']));

        //原值计算
        if ($postData['dataCount'] > 1 && $this->cal === true) {
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
                    if (in_array($field, $this->rangeArr[0], true) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $this->binaryFind($val, 0, count($this->rangeArr[1]) - 1, $this->rangeArr[1]);
                    } elseif (in_array($field, $this->rangeArrRatio[0], true) && is_numeric($val)) {
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
        //39社保人数同比 SOCNUM_yoy

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
            'SOCNUM_yoy' => null,
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
        //39社保人数同比
        $origin = $this->socnum_yoy($origin);

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

    //39社保人数同比 socnum_yoy
    private function socnum_yoy($origin)
    {
        foreach ($origin as $year => $val) {
            //去年
            $lastYear = $year - 1;

            //如果去年没数据
            if (!isset($origin[$lastYear]) || !is_numeric($origin[$lastYear][8])) {
                array_push($origin[$year], null);
                continue;
            }

            //如果今年没数据
            if (!is_numeric($origin[$year][8])) {
                array_push($origin[$year], null);
                continue;
            }

            //两年都有数据
            $last = $origin[$lastYear][8];
            $now = $origin[$year][8];

            if ($last === 0) {
                array_push($origin[$year], null);
                continue;
            }

            array_push($origin[$year], ($now - $last) / abs($last));
        }

        return $origin;
    }


}
