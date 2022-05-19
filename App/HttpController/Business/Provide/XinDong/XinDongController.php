<?php

namespace App\HttpController\Business\Provide\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\EntDb\EntDbEnt;
use App\HttpController\Models\EntDb\EntDbFinance;
use App\HttpController\Models\EntDb\EntDbTzList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\DaXiang\DaXiangService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\FinanceRange;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\Sms\SmsService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\Score\xds;
use App\HttpController\Service\XinDong\XinDongService;
use Carbon\Carbon;
use EasySwoole\Redis\Redis;
use wanghanwanghan\someUtils\control;
use function GuzzleHttp\Psr7\uri_for;
use EasySwoole\Component\Csp;
class XinDongController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = 500;
            $this->responsePaging = null;
            $this->responseData = $res[$this->cspKey];
            $this->spendMoney = 0;
            $this->responseMsg = empty($this->responseMsg) ? '请求超时' : $this->responseMsg;
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    //发送短信
    function sendSms(): bool
    {
        $orderId = $this->getRequestData('orderId');//流水号
        $phone = $this->getRequestData('phone');//法人手机号
        $vCode = $this->getRequestData('vCode');//验证码

        $this->csp->add($this->cspKey, function () use ($orderId, $phone, $vCode) {
            return SmsService::getInstance()->comm($phone, $vCode);
        });

        return $this->checkResponse([$this->cspKey => [
            'code' => 200,
            'paging' => null,
            'result' => CspService::getInstance()->exec($this->csp, $this->cspTimeout),
            'msg' => null,
        ]]);
    }

    //产品标准
    function getProductStandard(): bool
    {
        $entName = $this->getRequestData('entName');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $this->csp->add($this->cspKey, function () use ($entName, $page, $pageSize) {
            return XinDongService::getInstance()->getProductStandard($entName, $page, $pageSize);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //企业基本信息
    function getRegisterInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');

        $postData = [
            'entName' => $entName,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new TaoShuService())->setCheckRespFlag(true)->post($postData, 'getRegisterInfo');
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //单年基础数区间
    function getFinanceBaseData(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $this->getRequestData('year', 2019),
            'dataCount' => $this->getRequestData('dataCount', 1),
        ];

        $beginYear = $this->getRequestData('year', 2019) - 0;
        $dataCount = $this->getRequestData('dataCount', 1) - 0;

        $range = FinanceRange::getInstance()->getRange('range');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio');

        //周伯通或者客一客，天创信用
        if ($this->userId === 35 || $this->userId === 51 || $this->userId === 54) {
            if ($beginYear === 2021 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2020 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2019 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2018 && $dataCount === 1) {
                $a = null;
            } else {
                return $this->writeJson(201, null, null, '参数错误');
            }
        }
        //天创信用
        if ($this->userId === 52) {
            if ($beginYear === 2022 && $dataCount <= 3) {
                $a = null;
            } else if ($beginYear === 2021 && $dataCount <= 3) {
                $a = null;
            } elseif ($beginYear === 2020 && $dataCount <= 3) {
                $a = null;
            } elseif ($beginYear === 2019 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2018 && $dataCount === 1) {
                $a = null;
            } else {
                return $this->writeJson(201, null, null, '参数错误');
            }
        }

        if ($this->userId === 1) {
            $range = FinanceRange::getInstance()->getRange('range_yuanqi');
            $ratio = FinanceRange::getInstance()->getRange('rangeRatio_yuanqi');
        }

        if (is_numeric($beginYear) && $beginYear >= 2013 && $beginYear <= date('Y')) {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //返原值专用
    function getFinanceOriginal(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $this->getRequestData('year', 2020),
            'dataCount' => $this->getRequestData('dataCount', 5),
            'cal' => $this->getRequestData('cal', 0),
            'flag' => $this->getRequestData('flag', 0),
        ];

        $beginYear = $this->getRequestData('year', 2020);

        if (is_numeric($beginYear) && $beginYear >= 2013 && $beginYear <= date('Y')) {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(!!$postData['flag'])
                    ->setCal(!!$postData['cal'])
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //狮桥
    function getFinanceBaseDataSQ(): bool
    {
        $beginYear = 2020;
        $dataCount = 2;

        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $check = EntDbEnt::create()->where('name', $this->getRequestData('entName'))->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->order('ANCHEYEAR', 'DESC')
                ->limit(0, 2)
                ->field([
                    'ASSGRO',
                    'LIAGRO',
                    'MAIBUSINC',
                    'NETINC',
                    'PROGRO',
                    'RATGRO',
                    'TOTEQU',
                    'VENDINC',
                    'ANCHEYEAR',
                ])->all();
        }

        if (!empty($f_info)) {
            $this->spendMoney = 0;
            $tmp = [];
            foreach ($f_info as $one) {
                $tmp[$one->ANCHEYEAR . ''] = obj2Arr($one);
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        if ($res[$this->cspKey]['code'] === 200 && !empty($res[$this->cspKey]['result'])) {
            $indexTable = [
                '0' => 'O',
                '1' => 'C',
                '2' => 'E',
                '3' => 'I',
                '4' => 'G',
                '5' => 'A',
                '6' => 'H',
                '7' => 'F',
                '8' => 'D',
                '9' => 'B',
                '.' => '*',
                '-' => 'J',
            ];
            foreach ($res[$this->cspKey]['result'] as $year => $oneYearData) {
                foreach ($oneYearData as $field => $num) {
                    if ($field === 'ispublic' || $field === 'SOCNUM' || $field === 'ANCHEYEAR') {
                        unset($res[$this->cspKey]['result'][$year][$field]);
                        continue;
                    }
                    $tmp = strtr($num, $indexTable);
                    $tmp = current(explode('*', $tmp));
                    if ($tmp[0] === 'J') {
                        //负数
                        if (strlen($tmp) >= 3) {
                            $tmp = substr($tmp, 0, -1);
                            $tmp = 'X' . $tmp;//有X说明要末尾补0
                        }
                    } else {
                        //正数
                        if (strlen($tmp) >= 2) {
                            $tmp = substr($tmp, 0, -1);
                            $tmp = 'X' . $tmp;//有X说明要末尾补0
                        }
                    }
                    $res[$this->cspKey]['result'][$year][$field] = $tmp;
                }
            }
        }

        return $this->checkResponse($res);
    }

    /**
     * 每日查询上限
     */
    public function limitEntNumByUserId($keyName, $entName, $maxNum)
    {

        $time = date('Ymd', time());
        $key = $keyName . $this->userId . $time;
        $redis = \EasySwoole\RedisPool\Redis::defer('redis');
        $redis->select(14);
        $num = $redis->hlen($key);
//        dingAlarmMarkdownForWork('每日查询上限',[['name'=>'数量','msg'=>$num],['name'=>'名称','msg'=>$entName]]);
        if ($num >= $maxNum) {
            return true;
        } else if (!$num) {
            $redis->expire($key, 86400);
        }
        $redis->hset($key, $entName, $entName);
        return false;
    }

    //益博睿
    function getFinanceBaseDataYBR(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $year = $this->getRequestData('year', '');
        $userInputYear = explode(',', trim($year, ','));
        if (count($userInputYear)>3) {
            return $this->writeJson(201, null, null, '只能最多请求三年的数据');
        }
        $beginYear = 2021;
        $dataCount = 5;

        $this->spendMoney = 1;

        if ($this->limitEntNumByUserId(__FUNCTION__, $entName, 10000)) {
            return $this->writeJson(201, null, null, '请求次数已经达到上限10000');
        }
        if (empty($entName)) {
            return $this->writeJson(201, null, null, 'entName不能是空');
        }
        if (empty($year) || empty($userInputYear)) {
            return $this->writeJson(201, null, null, 'year不能是空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $check = EntDbEnt::create()->where('name', $entName)->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->where('ANCHEYEAR', [2021, 2020, 2019,2018,2017], 'IN')
                ->field([
                    'ASSGRO',
                    'LIAGRO',
                    'MAIBUSINC',
                    'NETINC',
                    'PROGRO',
                    'RATGRO',
                    'TOTEQU',
                    'VENDINC',
                    'ANCHEYEAR',
                ])->all();
        }

        if (!empty($f_info)) {
            $tmp = [];
            foreach ($f_info as $one) {
                //只能是year里的年份
                if (in_array($one->ANCHEYEAR . '', $userInputYear, true)) {
                    $tmp[$one->ANCHEYEAR . ''] = obj2Arr($one);
                }
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        if ($res[$this->cspKey]['code'] === 200 && !empty($res[$this->cspKey]['result'])) {
            $indexTable = [
                '0' => 'O',
                '1' => 'C',
                '2' => 'E',
                '3' => 'I',
                '4' => 'G',
                '5' => 'A',
                '6' => 'H',
                '7' => 'F',
                '8' => 'D',
                '9' => 'B',
                '.' => '*',
                '-' => 'J',
            ];
            foreach ($res[$this->cspKey]['result'] as $year => $oneYearData) {
                if (in_array($year, $userInputYear)) {
                    foreach ($oneYearData as $field => $num) {
                        if ($field === 'ispublic' || $field === 'ANCHEYEAR') {
                            unset($res[$this->cspKey]['result'][$year][$field]);
                            continue;
                        }
                        $res[$this->cspKey]['result'][$year][$field] = strtr($num, $indexTable);
                    }
                } else {
                    unset($res[$this->cspKey]['result'][$year]);
                }
            }
        }

        return $this->checkResponse($res);
    }

    /*
     * 青岛格兰德信用管理咨询有限公司
     * 获取三年的财务数据
     */
    function getFinanceBaseDataGLD(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $year = $this->getRequestData('year', '');
        $userInputYear = explode(',', trim($year, ','));

        $beginYear = 2021;
        $dataCount = 3;

        $this->spendMoney = 1;

        if ($this->limitEntNumByUserId(__FUNCTION__, $entName, 50)) {
            return $this->writeJson(201, null, null, '请求次数已经达到上限50');
        }
        if (empty($entName)) {
            return $this->writeJson(201, null, null, 'entName不能是空');
        }
        if (empty($year) || empty($userInputYear)) {
            return $this->writeJson(201, null, null, 'year不能是空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $check = EntDbEnt::create()->where('name', $entName)->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->where('ANCHEYEAR', [2021, 2020, 2019], 'IN')
                ->field([
                            'ASSGRO',
                            'LIAGRO',
                            'MAIBUSINC',
                            'NETINC',
                            'PROGRO',
                            'RATGRO',
                            'TOTEQU',
                            'VENDINC',
                            'ANCHEYEAR',
                        ])->all();
        }

        if (!empty($f_info)) {
            $tmp = [];
            foreach ($f_info as $one) {
                //只能是year里的年份
                if (in_array($one->ANCHEYEAR . '', $userInputYear, true)) {
                    $tmp[$one->ANCHEYEAR . ''] = obj2Arr($one);
                }
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        if ($res[$this->cspKey]['code'] === 200 && !empty($res[$this->cspKey]['result'])) {
            $indexTable = [
                '0' => 'O',
                '1' => 'C',
                '2' => 'E',
                '3' => 'I',
                '4' => 'G',
                '5' => 'A',
                '6' => 'H',
                '7' => 'F',
                '8' => 'D',
                '9' => 'B',
                '.' => '*',
                '-' => 'J',
            ];
            foreach ($res[$this->cspKey]['result'] as $year => $oneYearData) {
                if (in_array($year, $userInputYear)) {
                    foreach ($oneYearData as $field => $num) {
                        if ($field === 'ispublic' || $field === 'ANCHEYEAR') {
                            unset($res[$this->cspKey]['result'][$year][$field]);
                            continue;
                        }
                        $res[$this->cspKey]['result'][$year][$field] = strtr($num, $indexTable);
                    }
                } else {
                    unset($res[$this->cspKey]['result'][$year]);
                }
            }
        }

        return $this->checkResponse($res);
    }

    /*
     * 大连倍通商业信用咨询有限公司
     * 获取三年的财务数据
     */
    function getFinanceBaseDataDLBT(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $year = $this->getRequestData('year', '');
        $userInputYear = explode(',', trim($year, ','));

        $beginYear = 2021;
        $dataCount = 3;

        $this->spendMoney = 1;

        if ($this->limitEntNumByUserId(__FUNCTION__, $entName, 50)) {
            return $this->writeJson(201, null, null, '请求次数已经达到上限50');
        }
        if (empty($entName)) {
            return $this->writeJson(201, null, null, 'entName不能是空');
        }
        if (empty($year) || empty($userInputYear)) {
            return $this->writeJson(201, null, null, 'year不能是空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $check = EntDbEnt::create()->where('name', $entName)->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->where('ANCHEYEAR', [2021, 2020, 2019], 'IN')
                ->field([
                            'ASSGRO',
                            'LIAGRO',
                            'MAIBUSINC',
                            'NETINC',
                            'PROGRO',
                            'RATGRO',
                            'TOTEQU',
                            'VENDINC',
                            'ANCHEYEAR',
                        ])->all();
        }

        if (!empty($f_info)) {
            $tmp = [];
            foreach ($f_info as $one) {
                //只能是year里的年份
                if (in_array($one->ANCHEYEAR . '', $userInputYear, true)) {
                    $tmp[$one->ANCHEYEAR . ''] = obj2Arr($one);
                }
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        if ($res[$this->cspKey]['code'] === 200 && !empty($res[$this->cspKey]['result'])) {
            $indexTable = [
                '0' => 'O',
                '1' => 'C',
                '2' => 'E',
                '3' => 'I',
                '4' => 'G',
                '5' => 'A',
                '6' => 'H',
                '7' => 'F',
                '8' => 'D',
                '9' => 'B',
                '.' => '*',
                '-' => 'J',
            ];
            foreach ($res[$this->cspKey]['result'] as $year => $oneYearData) {
                if (in_array($year, $userInputYear)) {
                    foreach ($oneYearData as $field => $num) {
                        if ($field === 'ispublic' || $field === 'ANCHEYEAR') {
                            unset($res[$this->cspKey]['result'][$year][$field]);
                            continue;
                        }
                        $res[$this->cspKey]['result'][$year][$field] = strtr($num, $indexTable);
                    }
                } else {
                    unset($res[$this->cspKey]['result'][$year]);
                }
            }
        }

        return $this->checkResponse($res);
    }

    //上海圈讯
    function getFinanceBaseDataQX(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $year = $this->getRequestData('year', '');
        $userInputYear = explode(',', trim($year, ','));

        $beginYear = 2021;
        $dataCount = 3;

        $this->spendMoney = 1;

        if ($this->limitEntNumByUserId(__FUNCTION__, $entName, 50)) {
            return $this->writeJson(201, null, null, '请求次数已经达到上限50');
        }
        if (empty($entName)) {
            return $this->writeJson(201, null, null, 'entName不能是空');
        }
        if (empty($year) || empty($userInputYear)) {
            return $this->writeJson(201, null, null, 'year不能是空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $check = EntDbEnt::create()->where('name', $entName)->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->where('ANCHEYEAR', [2021, 2020, 2019], 'IN')
                ->field([
                            'ASSGRO',
                            'LIAGRO',
                            'MAIBUSINC',
                            'NETINC',
                            'PROGRO',
                            'RATGRO',
                            'TOTEQU',
                            'VENDINC',
                            'ANCHEYEAR',
                        ])->all();
        }

        if (!empty($f_info)) {
            $tmp = [];
            foreach ($f_info as $one) {
                //只能是year里的年份
                if (in_array($one->ANCHEYEAR . '', $userInputYear, true)) {
                    $tmp[$one->ANCHEYEAR . ''] = obj2Arr($one);
                }
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setCal(false)
                    ->getFinanceData($postData, false);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        if ($res[$this->cspKey]['code'] === 200 && !empty($res[$this->cspKey]['result'])) {
            $indexTable = [
                '0' => 'O',
                '1' => 'C',
                '2' => 'E',
                '3' => 'I',
                '4' => 'G',
                '5' => 'A',
                '6' => 'H',
                '7' => 'F',
                '8' => 'D',
                '9' => 'B',
                '.' => '*',
                '-' => 'J',
            ];
            foreach ($res[$this->cspKey]['result'] as $year => $oneYearData) {
                if (in_array($year, $userInputYear)) {
                    foreach ($oneYearData as $field => $num) {
                        if ($field === 'ispublic' || $field === 'ANCHEYEAR') {
                            unset($res[$this->cspKey]['result'][$year][$field]);
                            continue;
                        }
                        $res[$this->cspKey]['result'][$year][$field] = strtr($num, $indexTable);
                    }
                } else {
                    unset($res[$this->cspKey]['result'][$year]);
                }
            }
        }

        return $this->checkResponse($res);
    }

    //众望
    function getFinanceBaseDataZW(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $beginYear = 2019;
        $dataCount = 3;

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $range = FinanceRange::getInstance()->getRange('range_touzhong');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio_touzhong');

        $ent_info = EntDbEnt::create()->where('name', $entName)->get();

        if (!empty($ent_info) && !empty(($f_info = EntDbFinance::create()->where('cid', $ent_info->getAttr('id'))->all()))) {
            // $this->spendMoney = 0;
            $origin = [];
            foreach ($f_info as $one) {
                $origin[$one->getAttr('ANCHEYEAR') . ''] = obj2Arr($one);
            }
            $obj = new LongXinService();
            $readyReturn = $obj->exprHandle($origin);
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (in_array($field, $range[0], true) && is_numeric($val)) {
                        !is_numeric($val) ?: $val = $val * 10000;
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($range[1]) - 1, $range[1]);
                    } elseif (in_array($field, $ratio[0], true) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($ratio[1]) - 1, $ratio[1]);
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
            krsort($readyReturn);
            for ($i = $beginYear; $i > $beginYear - $dataCount; $i--) {
                $tmp[$i] = $readyReturn[$i] ?? $readyReturn[$i . ''];
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData, true);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        $result = [];

        foreach ($res[$this->cspKey]['result'] as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (!is_array($val)) {
                    $result[$year][$field] = $val;
                } else {
                    $result[$year][$field] = $val['name'];
                }
            }
        }

        //改键
        $result = control::changeArrKey($result, [
            'ASSGRO' => 'ASSGRO_REL',
            'LIAGRO' => 'LIAGRO_REL',
            'VENDINC' => 'VENDINC_REL',
            'MAIBUSINC' => 'MAIBUSINC_REL',
            'PROGRO' => 'PROGRO_REL',
            'NETINC' => 'NETINC_REL',
            'RATGRO' => 'RATGRO_REL',
            'TOTEQU' => 'TOTEQU_REL',
            'CA_ASSGRO' => 'CA_ASSGROL',
            'ASSGRO_yoy' => 'ASSGRO_REL_yoy',
            'LIAGRO_yoy' => 'LIAGRO_REL_yoy',
            'VENDINC_yoy' => 'VENDINC_REL_yoy',
            'MAIBUSINC_yoy' => 'MAIBUSINC_REL_yoy',
            'PROGRO_yoy' => 'PROGRO_REL_yoy',
            'NETINC_yoy' => 'NETINC_REL_yoy',
            'RATGRO_yoy' => 'RATGRO_REL_yoy',
            'TOTEQU_yoy' => 'TOTEQU_REL_yoy',
        ]);

        //留下要的字段
        $save = [
            'ASSGRO_REL', 'LIAGRO_REL', 'VENDINC_REL', 'MAIBUSINC_REL', 'PROGRO_REL', 'NETINC_REL',
            'RATGRO_REL', 'TOTEQU_REL', 'SOCNUM', 'ASSGRO_REL_yoy', 'LIAGRO_REL_yoy', 'VENDINC_REL_yoy',
            'MAIBUSINC_REL_yoy', 'PROGRO_REL_yoy', 'NETINC_REL_yoy', 'RATGRO_REL_yoy', 'TOTEQU_REL_yoy',
            'C_INTRATESL', 'ASSGRO_C_INTRATESL', 'ROAL', 'DEBTL', 'ATOL'
        ];

        foreach ($result as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (in_array($field, $save, true)) {
                    $temp[$year][$field] = $val;
                }
            }
        }

        $res[$this->cspKey]['result'] = $temp;

        return $this->checkResponse($res);
    }

    //销售易
    function getFinanceBaseDataXSY(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $beginYear = 2020;
        $dataCount = 1;

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $range = FinanceRange::getInstance()->getRange('range');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio');

        $ent_info = EntDbEnt::create()->where('name', $entName)->get();

        if (!empty($ent_info) && !empty(($f_info = EntDbFinance::create()->where('cid', $ent_info->getAttr('id'))->all()))) {
            // $this->spendMoney = 0;
            $origin = [];
            foreach ($f_info as $one) {
                $origin[$one->getAttr('ANCHEYEAR') . ''] = obj2Arr($one);
            }
            $obj = new LongXinService();
            $readyReturn = $obj->exprHandle($origin);
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (in_array($field, $range[0], true) && is_numeric($val)) {
                        !is_numeric($val) ?: $val = $val * 10000;
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($range[1]) - 1, $range[1]);
                    } elseif (in_array($field, $ratio[0], true) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($ratio[1]) - 1, $ratio[1]);
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
            krsort($readyReturn);
            for ($i = $beginYear; $i > $beginYear - $dataCount; $i--) {
                $tmp[$i] = $readyReturn[$i] ?? $readyReturn[$i . ''];
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData, true);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        $result = [];

        foreach ($res[$this->cspKey]['result'] as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (!is_array($val)) {
                    $result[$year][$field] = $val;
                } else {
                    $result[$year][$field] = $val['name'];
                }
            }
        }

        //留下要的字段
        $save = [
            'ASSGRO', 'LIAGRO', 'VENDINC',
            'MAIBUSINC', 'PROGRO', 'NETINC',
            'RATGRO', 'TOTEQU',
        ];

        foreach ($result as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (in_array($field, $save, true)) {
                    $temp[$year][$field] = $val;
                }
            }
        }

        $res[$this->cspKey]['result'] = $temp;

        return $this->checkResponse($res);
    }

    //投中
    function getFinanceBaseDataTZ(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $beginYear = 2020;

        if (empty($entName)) {
            return $this->checkResponse([$this->cspKey => [
                'code' => 201,
                'paging' => null,
                'result' => null,
                'msg' => 'entName不能是空',
            ]]);
        }

        $check = EntDbTzList::create()->where('key', $entName)->get();

        if (empty($check)) {
            $dataCount = 6;
            EntDbTzList::create()->data([
                'key' => $entName,
                'creditCodeRegNo' => $this->getRequestData('code', ''),
                'type' => '',
            ])->save();
        } else {
            if ($check->getAttr('type') === '第一批') {
                $dataCount = 1;
            } else {
                $dataCount = 6;
            }
        }

        $dataCount = 6;

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $range = FinanceRange::getInstance()->getRange('range_touzhong');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio_touzhong');

        $check = EntDbEnt::create()->where('name', $entName)->get();

        if (empty($check)) {
            $f_info = [];
        } else {
            $f_info = EntDbFinance::create()
                ->where('cid', $check->getAttr('id'))
                ->all();
        }

        if (!empty($f_info)) {
            $origin = [];
            foreach ($f_info as $one) {
                $origin[$one->getAttr('ANCHEYEAR') . ''] = obj2Arr($one);
            }
            $obj = new LongXinService();
            $readyReturn = $obj->exprHandle($origin);
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (in_array($field, $range[0], true) && is_numeric($val)) {
                        !is_numeric($val) ?: $val = $val * 10000;
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($range[1]) - 1, $range[1]);
                    } elseif (in_array($field, $ratio[0], true) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($ratio[1]) - 1, $ratio[1]);
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
            krsort($readyReturn);
            for ($i = $beginYear; $i > $beginYear - $dataCount; $i--) {
                $tmp[$i] = $readyReturn[$i] ?? $readyReturn[$i . ''];
            }
            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setRangeIsYuan(true)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData, true);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        $result = [];

        foreach ($res[$this->cspKey]['result'] as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (!is_array($val)) {
                    //社保人数改int在这里
                    $result[$year][$field] = is_numeric($val) ? $val - 0 : $val;
                } else {
                    $result[$year][$field] = $val['name'];
                }
            }
        }

        //改键
        $result = control::changeArrKey($result, [
            'ASSGRO' => 'ASSGRO_REL',
            'LIAGRO' => 'LIAGRO_REL',
            'VENDINC' => 'VENDINC_REL',
            'MAIBUSINC' => 'MAIBUSINC_REL',
            'PROGRO' => 'PROGRO_REL',
            'NETINC' => 'NETINC_REL',
            'RATGRO' => 'RATGRO_REL',
            'TOTEQU' => 'TOTEQU_REL',
            'CA_ASSGRO' => 'CA_ASSGROL',
            'ASSGRO_yoy' => 'ASSGRO_REL_yoy',
            'LIAGRO_yoy' => 'LIAGRO_REL_yoy',
            'VENDINC_yoy' => 'VENDINC_REL_yoy',
            'MAIBUSINC_yoy' => 'MAIBUSINC_REL_yoy',
            'PROGRO_yoy' => 'PROGRO_REL_yoy',
            'NETINC_yoy' => 'NETINC_REL_yoy',
            'RATGRO_yoy' => 'RATGRO_REL_yoy',
            'TOTEQU_yoy' => 'TOTEQU_REL_yoy',
        ]);

        //留下要的字段 36个
        $save = [
            'ASSGRO_REL', 'ASSGRO_REL_yoy', 'LIAGRO_REL', 'LIAGRO_REL_yoy', 'VENDINC_REL',
            'VENDINC_REL_yoy', 'MAIBUSINC_REL', 'MAIBUSINC_REL_yoy', 'PROGRO_REL', 'PROGRO_REL_yoy',
            'NETINC_REL', 'NETINC_REL_yoy', 'RATGRO_REL', 'RATGRO_REL_yoy', 'TOTEQU_REL',
            'TOTEQU_REL_yoy', 'SOCNUM', 'C_ASSGROL', 'C_ASSGROL_yoy', 'A_ASSGROL', 'A_ASSGROL_yoy',
            'CA_ASSGROL', 'CA_ASSGROL_yoy', 'C_INTRATESL', 'ASSGRO_C_INTRATESL', 'A_VENDINCL', 'A_VENDINCL_yoy',
            'A_PROGROL', 'A_PROGROL_yoy', 'ROAL', 'ROE_AL', 'ROE_BL', 'DEBTL', 'EQUITYL', 'ATOL', 'MAIBUSINC_RATIOL',
        ];

        foreach ($result as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (in_array($field, $save, true)) {
                    $temp[$year][$field] = $val;
                }
            }
        }

        $res[$this->cspKey]['result'] = $temp;

        return $this->checkResponse($res);
    }

    //六棱镜
    function getFinanceBaseDataLLJ(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $beginYear = 2020;
        $dataCount = 3;

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $range = FinanceRange::getInstance()->getRange('range_liulengjing');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio_liulengjing');

        $ent_info = EntDbEnt::create()->where('name', $entName)->get();

        $ANCHEYEAR = [];
        for ($i = $beginYear; $i >= $beginYear - $dataCount; $i--) {
            $ANCHEYEAR[] = $i;
        }

        $f_info = EntDbFinance::create()
            ->where('cid', $ent_info->getAttr('id'))
            ->where('ANCHEYEAR', $ANCHEYEAR, 'IN')
            ->order('ANCHEYEAR', 'DESC')
            ->all();

        if (!empty($ent_info) && !empty($f_info)) {
            // $this->spendMoney = 0;
            $origin = [];
            foreach ($f_info as $one) {
                $origin[$one->getAttr('ANCHEYEAR') . ''] = obj2Arr($one);
            }
            $obj = new LongXinService();
            $readyReturn = $obj->exprHandle($origin);
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (isset($range[1][$field]) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind(
                            $val, 0, count($range[1][$field]) - 1, $range[1][$field]
                        );
                    } elseif (isset($ratio[1][$field]) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind(
                            $val, 0, count($ratio[1][$field]) - 1, $ratio[1][$field]
                        );
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
            krsort($readyReturn);
            for ($i = $beginYear; $i > $beginYear - $dataCount; $i--) {
                $tmp[$i] = $readyReturn[$i] ?? $readyReturn[$i . ''];
            }

            $res = [$this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => $tmp,
                'msg' => null,
            ]];
        } else {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData, true);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        $result = [];

        foreach ($res[$this->cspKey]['result'] as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (!is_array($val)) {
                    $result[$year][$field] = $val;
                } else {
                    $result[$year][$field] = $val['name'];
                }
            }
        }

        //留下要的字段
        $save = [
            'VENDINC', 'NETINC', 'MAIBUSINC_RATIOL',
            'DEBTL', 'VENDINC_CGR', 'VENDINC_yoy_ave_2',
            'NETINC_yoy_ave_2', 'RATGRO',
        ];

        foreach ($result as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (in_array($field, $save, true)) {
                    $temp[$year][$field] = $val;
                }
            }
        }

        $res[$this->cspKey]['result'] = $temp;

        return $this->checkResponse($res);
    }

    //连续n年基数+计算结果
    function getFinanceCalData(): bool
    {
        $beginYear = $this->getRequestData('year', '');

        $postData = [
            'entName' => $this->getRequestData('entName'),
            'code' => $this->getRequestData('code'),
            'beginYear' => $beginYear,
            'dataCount' => $this->getRequestData('dataCount', 3),//取最近几年的
        ];

        $range = FinanceRange::getInstance()->getRange('range');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio');

        if ($this->userId === 1) {
            $range = FinanceRange::getInstance()->getRange('range_yuanqi');
            $ratio = FinanceRange::getInstance()->getRange('rangeRatio_yuanqi');
        }

        if (is_numeric($beginYear) && $beginYear >= 2010 && $beginYear <= date('Y') - 1) {
            $this->csp->add($this->cspKey, function () use ($postData, $range, $ratio) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->setRangeArr($range, $ratio)
                    ->getFinanceData($postData);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //单年基础数区间 含 并表判断
    function getFinanceBaseMergeData(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $this->getRequestData('year', ''),
            'dataCount' => 1,//取最近几年的
        ];

        $beginYear = $this->getRequestData('year', '');

        if (is_numeric($beginYear) && $beginYear >= 2010 && $beginYear <= date('Y')) {
            $this->csp->add($this->cspKey, function () use ($postData) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getFinanceBaseMergeData($postData);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //连续n年基数 含 并表判断+计算结果
    function getFinanceCalMergeData(): bool
    {
        $beginYear = $this->getRequestData('year', '');

        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $this->getRequestData('dataCount', 3),//取最近几年的
        ];

        Carbon::now()->format('Ymd') > '20211220' ? $toRange = true : $toRange = false;

        if (is_numeric($beginYear) && $beginYear >= 2010 && $beginYear <= date('Y') - 1) {
            $this->csp->add($this->cspKey, function () use ($postData, $toRange) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getFinanceBaseMergeData($postData, $toRange);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //超级搜索
    function superSearch()
    {

    }

    //物流搜索
    function logisticsSearch()
    {

    }

    //开户行
    function getBankInfo(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
        ];

        $this->csp->add($this->cspKey . '1', function () use ($postData) {
            $postData = ['keyWord' => $postData['entName']];
            $ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');
            $res = (new LongDunService())
                ->setCheckRespFlag(true)
                ->get($ldUrl . 'ECICreditCode/GetCreditCodeNew', $postData);
            return $res['result'];
        });

        $this->csp->add($this->cspKey . '2', function () use ($postData) {
            $res = (new TaoShuService())
                ->setCheckRespFlag(true)
                ->post($postData, 'getRegisterInfo');
            return current($res['result']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        $Bank = $res[$this->cspKey . '1']['Bank'];
        $BankAccount = $res[$this->cspKey . '1']['BankAccount'];
        $FR = $res[$this->cspKey . '2']['FRDB'];
        $SHXYDM = $res[$this->cspKey . '2']['SHXYDM'];

        $res = [
            $this->cspKey => [
                'code' => 200,
                'paging' => null,
                'result' => [
                    'Bank' => $Bank,
                    'BankAccount' => $BankAccount,
                    'FR' => $FR,
                    'SHXYDM' => $SHXYDM,
                ],
                'msg' => null,
            ]
        ];

        return $this->checkResponse($res);
    }

    //企业联系方式
    function getEntLianXi(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntLianXi($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //除了蚂蚁以外的发过来的企业五要素
    function invEntList(): bool
    {
        $data['entName'] = $this->getRequestData('entName');
        $data['socialCredit'] = $this->getRequestData('socialCredit');
        $data['legalPerson'] = $this->getRequestData('legalPerson');
        $data['idCard'] = $this->getRequestData('idCard');
        $data['phone'] = $this->getRequestData('phone');
        $data['requestId'] = $this->requestId;
        $data['belong'] = 1;//provide公司id

        $this->csp->add($this->cspKey, function () use ($data) {
            return (new MaYiService())->authEnt($data);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCpwsList(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
            'pageSize' => 10,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getCpwsList($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getCpwsDetail(): bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getCpwsDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getKtggList(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
            'pageSize' => 10,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getKtggList($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getKtggDetail(): bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getKtggDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFyggList(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
            'pageSize' => 10,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getFyggList($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getFyggDetail(): bool
    {
        $postData = [
            'mid' => $this->getRequestData('mid'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getFyggDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getSxbzxr(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getSxbzxr($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getBzxr(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getBzxr($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getJobInfo(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
            'title' => $this->getRequestData('title'),
            'position' => $this->getRequestData('position'),
            'industry' => $this->getRequestData('industry'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getJobInfo($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getInv()
    {

    }

    function vcQueryList(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->vcQueryList($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function vcQueryDetail(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'id' => $this->getRequestData('id'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->vcQueryDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getNaCaoRegisterInfo(): bool
    {
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getNaCaoRegisterInfo($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntDetail(): bool
    {
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getEntNicName(): bool
    {
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        if (!empty($res[$this->cspKey]['result']['BASIC'])) {
            $res[$this->cspKey]['result'] = $res[$this->cspKey]['result']['BASIC']['nic_name'] ?? '';
        }

        return $this->checkResponse($res);
    }

    public function getFinanceDataTwo(): bool
    {

//        $entName = $this->request()->getRequestParam('entName') ?? '';
//        if (empty($entName)) {
//            return $this->writeJson(201, null, null, '公司名称不能是空');
//        }
//        $this->csp->add($this->cspKey . '_', function () use ($entName) {
//            return (new xds())->cwScoreTwo($entName);
//        });
////        $resOne = (new xds())->cwScoreTwo($entName);
////
////        $this->csp->add($this->cspKey,[
////            'code' => 200,
////            'paging' => null,
////            'result' => $resOne,
////            'msg' => '查询成功',
////            'checkRespFlag' => false,
////        ]);
//        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        CommonService::getInstance()->log4PHP($res,'info','getFinanceDataTwoResCC');
//        return $this->checkResponse($res);
        $entName = $this->request()->getRequestParam('entName');
//        $res = XinDongService::getInstance()->getFeaturesTwo($entName);
//        CommonService::getInstance()->log4PHP($res,'info','getFinanceDataTwoResCC');
//        return $this->checkResponse($res);


        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getFeaturesTwo($entName);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::getInstance()->log4PHP($res, 'info', 'getFinanceDataTwoResCC');
        return $this->checkResponse($res);
    }

    public function getFinanceDataForApi(): bool
    {
        $entName = $this->request()->getRequestParam('entName');
        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getFeaturesForApi($entName);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::getInstance()->log4PHP($res, 'info', 'getFinanceDataTwoResCC');
        return $this->checkResponse($res);
    }

    function getCompanyList(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'page' => $this->getRequestData('page', 1),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getCompanyList($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getNicCode(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName'),
            'code' => $this->getRequestData('code'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getNicCode($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //发票归集
    function collectInvoice(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $start = $this->getRequestData('start');//YYYY-MM-DD
        $stop = $this->getRequestData('stop');//YYYY-MM-DD

        $postData = [
            'nsrsbh' => $nsrsbh,
            'start' => $start,
            'stop' => $stop,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000519($postData['nsrsbh'], $postData['start'], $postData['stop']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //发票提取
    function getInvoice(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $rwh = $this->getRequestData('rwh');
        $page = $this->getRequestData('page');
        $pageSize = $this->getRequestData('pageSize');

        $postData = [
            'nsrsbh' => $nsrsbh,
            'rwh' => $rwh,
            'page' => trim($page),
            'pageSize' => trim($pageSize),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000523($postData['nsrsbh'], $postData['rwh'], $postData['page'], $postData['pageSize']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //发票认证
    function invCertification(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $Period = $this->getRequestData('Period');
        $BillType = $this->getRequestData('BillType');
        $DeductibleMode = $this->getRequestData('DeductibleMode');
        $InvoiceList = $this->getRequestData('InvoiceList');

        //'InvoiceCode' => 'xxx',//发票代码 增值税发票时，不为空 缴款书发票时，可为空
        //'InvoiceNumber' => 'xxx',
        //'NotDeductibleType' => 'xxx',//不抵扣类型 认证模式=4时（不抵扣勾选时需要）需要传。不传则默认5 1：用于非应税项目 2：用于免税项目 3：用于集体福利或者个人消费 4：遭受非正常损失 5：其他
        //'ValidTax' => '',//有效税额

        $postData = [
            'nsrsbh' => $nsrsbh,
            'Period' => $Period,
            'BillType' => $BillType,
            'DeductibleMode' => $DeductibleMode,
            'InvoiceList' => jsonDecode($InvoiceList),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000514($postData['nsrsbh'], $postData['Period'], $postData['BillType'], $postData['DeductibleMode'], $postData['InvoiceList']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }
    //
    function getEntInfoByName(): bool
    {
        $entName =  $this->getRequestData('entName'); 
        if(!$entName){
            return  $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $entNames = [];
        $entNames[$entName] = $entName;

        // 如果包含中文或者英文括号  需要中英文括号都查下
        if (
            strpos($entName, '）') !== false &&
            strpos($entName, '（') !== false 
        ) {
            $newEntName =  str_replace(['（','）'], ['(',')'], $entName);
            $entNames[$newEntName] = $newEntName;
        } 

        if (
            strpos($entName, ')') !== false &&
            strpos($entName, '(') !== false 
        ) {
            $newEntName =  str_replace(['(',')'], ['（','）'],  $entName);
            $entNames[$newEntName] = $newEntName;
        } 

        $this->csp->add($this->cspKey, function () use ($entNames) {
            return (new XinDongService())
                ->getEntInfoByName($entNames) ;
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        // $res = (new XinDongService())
        //     ->getEntInfoByName($entNames);

        return $this->checkResponse($res);

    }

    function matchCompanyByFuzzyName(): bool
    {
        $entName =  $this->getRequestData('entName'); 
        if(!$entName){
            return  $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $entNames = [];
        $entNames[$entName] = $entName;

        // 如果包含中文或者英文括号  需要中英文括号都查下
        if (
            strpos($entName, '）') !== false &&
            strpos($entName, '（') !== false 
        ) {
            $newEntName =  str_replace(['（','）'], ['(',')'], $entName);
            $entNames[$newEntName] = $newEntName;
        } 

        if (
            strpos($entName, ')') !== false &&
            strpos($entName, '(') !== false 
        ) {
            $newEntName =  str_replace(['(',')'], ['（','）'],  $entName);
            $entNames[$newEntName] = $newEntName;
        } 

        $this->csp->add($this->cspKey, function () use ($entNames) {
            return (new XinDongService())
                ->getEntInfoByName($entNames) ;
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        // $res = (new XinDongService())
        //     ->getEntInfoByName($entNames);

        return $this->checkResponse($res);

    }

    function testCsp(): bool
    {
          
        $csp = new \EasySwoole\Component\Csp(); 

        for ($i=1; $i < 150; $i++) { 
            $csp->add('t'.$i, function () {
                \co::sleep(2);
                return 't'.$i.' result';
            }); 
        }
    
        $res = ($csp->exec());
        // $a = [];
        // $this->csp->add($this->cspKey, function () use (&$a) {
        //     $res =  (new XinDongService())
        //     ->testCsp1() ;
        //     $this->cspData[] = $res;
        //     return $res ;
        // });
        // $newKey=  control::getUuid();
        // $this->csp->add( $newKey, function () use (&$a) {
        //     $res =  (new XinDongService())
        //     ->testCsp2() ;
        //     $this->cspData[] = $res;
        //     return $res ;
        // });

        // $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
         CommonService::getInstance()->log4PHP('testCsp'.
            json_encode( 
                $res
            ) );

        // $res = (new XinDongService())
        //     ->getEntInfoByName($entNames);

         

        $newres = [
            't1' => [
                'code' => 200,
                'paging' => null,
                'result' => [
                     $res
                ],
                'msg' => null,
            ], 
        ];

        return $this->checkResponse($newres);

    }
}