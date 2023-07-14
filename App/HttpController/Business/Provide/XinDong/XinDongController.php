<?php

namespace App\HttpController\Business\Provide\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Api\NeoCrmPendingEnt;
use App\HttpController\Models\EntDb\EntDbEnt;
use App\HttpController\Models\EntDb\EntDbFinance;
use App\HttpController\Models\EntDb\EntDbTzList;
use App\HttpController\Models\Provide\RequestRecode;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyLiquidation;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\Export\Report\Word\ReportWordService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\JingZhun\JingZhunService;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\FinanceRange;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\MaYi\MaYiService;
use App\HttpController\Service\QiKe\ZhaoTouBiaoService;
use App\HttpController\Service\Sms\SmsService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\Score\FenShuService;
use App\HttpController\Service\XinDong\XinDongService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

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
            $this->responseData = $res[$this->cspKey]['result'] ?? $res[$this->cspKey]['data'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    //启客招投标接口
    function ztbListq(): bool
    {
        $keyword = $this->getRequestData('keyword', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);

        $postData = [
            'keyword' => $keyword,
            'page' => $page - 0,
            'pageSize' => $pageSize - 0,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new ZhaoTouBiaoService())->setCheckRespFlag(true)
                ->getList($postData['keyword'], $postData['page'], $postData['pageSize']);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        CommonService::getInstance()->log4PHP([4, $res], 'info', 'ztb101');

        return $this->checkResponse($res);
    }

    //启客招投标接口
    function ztbDetailq(): bool
    {
        $mid = $this->getRequestData('mid', '');

        $this->csp->add($this->cspKey, function () use ($mid) {
            return (new ZhaoTouBiaoService())
                ->setCheckRespFlag(true)
                ->getDetail($mid);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //销售易传过来的有问题企业
    function pendingEnt(): bool
    {
        $name = $this->getRequestData('name');
        $code = $this->getRequestData('code');
        $type = $this->getRequestData('type');
        $remark = $this->getRequestData('remark');

        $this->csp->add($this->cspKey, function () use ($name, $code, $remark, $type) {
            try {
                $obj = NeoCrmPendingEnt::create();
                if (!empty($name)) {
                    $obj->where('name', $name);
                } elseif (!empty($code)) {
                    $obj->where('code', $code);
                } else {
                    return null;
                }
                $res = $obj->where('created_at', strtotime('-7 day'), '>=')->all();
                if (!empty($res)) {
                    // 7天内只能提交一次
                    return null;
                }
            } catch (\Throwable $exception) {
            }
            try {
                NeoCrmPendingEnt::create()->data([
                    'name' => trim($name),
                    'code' => trim($code),
                    'type' => $type - 0,
                    'sended' => 0,
                    'repaired' => 0,
                    'remark' => trim($remark),
                    'created_at' => time(),
                    'updated_at' => time(),
                ])->save();
            } catch (\Throwable $exception) {
            }
            return null;
        });

        return $this->checkResponse([$this->cspKey => [
            'code' => 200,
            'paging' => null,
            'result' => CspService::getInstance()->exec($this->csp, $this->cspTimeout),
            'msg' => null,
        ]]);
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

    //单年基础数区间 信动用 zai把公司ip屏蔽了
    function getFinanceDataXD(): bool
    {
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $this->getRequestData('year', 2019),
            'dataCount' => $this->getRequestData('dataCount', 1),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getFinanceDataXD($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function getPersonalInformation(): bool
    {
        $idno = $this->getRequestData('idno', '');

        $this->csp->add($this->cspKey, function () use ($idno) {
            return (new LongXinService())
                ->getPersonalInformation($idno);
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
            if ($beginYear === 2022 && $dataCount <= 3) {
                $a = null;
            } elseif ($beginYear === 2021 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2020 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2019 && $dataCount === 1) {
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
        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => 2022,
            'dataCount' => 3,
        ];

        $page = 1;
        $gived = false;

        while (true) {
            $recode_info = RequestRecode::create()->addSuffix(date('Y'))->where([
                'userId' => $this->userId,
                'requestUrl' => '/provide/v1/xd/getFinanceBaseDataSQ',
                'responseCode' => 200,
            ])->page($page, 100)->field(['requestData', 'responseData'])->all();
            if (empty($recode_info)) break;
            foreach ($recode_info as $one) {
                $requestData = jsonDecode($one->getAttr('requestData'));
                $responseData = jsonDecode($one->getAttr('responseData'));
                if (!empty($requestData) && !empty($responseData)) {
                    // 找到当前企业的这条
                    if (isset($requestData['entName']) && $requestData['entName'] === $postData['entName']) {
                        foreach ($responseData as $year => $vals) {
                            if ($year - 0 === 2022) {
                                $gived = true;
                            }
                        }
                    }
                }
            }
            $page++;
        }

        if ($gived) {
            $this->spendMoney = 0;
        }

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->setCal(false)
                ->getFinanceData($postData, false);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

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

        // 看2021的有没有，是空的话，只给2020和2019
        if (isset($res[$this->cspKey]['result'][2022])) {
            $unset = 0;
            foreach ($res[$this->cspKey]['result'][2022] as $field => $vals) {
                if (empty($vals)) $unset++;
            }
            if ($unset >= 8) {
                unset($res[$this->cspKey]['result'][2022]);
            } else {
                unset($res[$this->cspKey]['result'][2020]);
            }
        }

        return $this->checkResponse($res);
    }

    //狮桥
    function getFinanceBaseDataSQ20230706(): bool
    {
        $beginYear = $this->getRequestData('year', '');

        if (!is_numeric($beginYear) || substr($beginYear, 0, 2) !== '20') {
            $beginYear = 2022;
        }

        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear - 0,
            'dataCount' => 1,
        ];

        CommonService::getInstance()->log4PHP($postData);

        $page = 1;
        $gived = false;

        while (true) {
            $recode_info = RequestRecode::create()->addSuffix(date('Y'))->where([
                'userId' => $this->userId,
                'requestUrl' => '/provide/v1/xd/getFinanceBaseDataSQ20230706',
                'responseCode' => 200,
            ])->page($page, 100)->field(['requestData', 'responseData'])->all();
            if (empty($recode_info)) break;
            foreach ($recode_info as $one) {
                $requestData = jsonDecode($one->getAttr('requestData'));
                $responseData = jsonDecode($one->getAttr('responseData'));
                if (!empty($requestData) && !empty($responseData)) {
                    // 找到当前企业的这条
                    if (isset($requestData['entName']) && $requestData['entName'] === $postData['entName']) {
                        foreach ($responseData as $year => $vals) {
                            if ($year - 0 === $beginYear) {
                                $gived = true;
                            }
                        }
                    }
                }
            }
            $page++;
        }

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->setCal(false)
                ->getFinanceData($postData, false);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

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


        if ($gived || empty(array_filter($res[$this->cspKey]['result'][$beginYear]))) {
            $this->spendMoney = 0;
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
        if (count($userInputYear) > 3) {
            return $this->writeJson(201, null, null, '只能最多请求三年的数据');
        }
        $beginYear = 2022;
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

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->setCal(false)
                ->getFinanceData($postData, false);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

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
                        !is_numeric($num) ?: $num = round($num, 2);
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
        $dataCount = 4;

        $this->spendMoney = 1;

        if ($this->limitEntNumByUserId(__FUNCTION__, $entName, 500)) {
            return $this->writeJson(201, null, null, '请求次数已经达到上限500');
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
                        !is_numeric($num) ?: $num = round($num, 2);
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
        $beginYear = 2021;

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
        $beginYear = 2021;
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
                    $readyReturn[$year][$field] = $val;

                    if (
                        in_array($field, [
                            'VENDINC',
                            'NETINC',
                            'RATGRO',
                        ]) &&
                        isset($range[1][$field]) && is_numeric($val)
                    ) {
                        $readyReturn[$year][$field] = $obj->binaryFind(
                            $val, 0, count($range[1][$field]) - 1, $range[1][$field]
                        );
                    }

                    if (
                        in_array($field, [
                            'VENDINC',
                            'NETINC',
                            'RATGRO',
                        ]) &&
                        isset($ratio[1][$field]) && is_numeric($val)
                    ) {
                        $readyReturn[$year][$field] = $obj->binaryFind(
                            $val, 0, count($ratio[1][$field]) - 1, $ratio[1][$field]
                        );
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
                    //->setRangeArr($range, $ratio)
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
            'VENDINC_yoy', 'PROGRO_yoy', 'RATGRO_yoy', 'ASSGRO_yoy', 'NETINC_yoy',
            'DEBTL', 'NALR', 'EQUITYL_new', 'MAIBUSINC_yoy', 'ATOL', 'OPM',
            'ROE_AL', 'ROE_BL', 'ROCA', 'NOR', 'ROAL', 'PMOTA', 'TBR',
        ];

        foreach ($result as $year => $arr) {
            foreach ($arr as $field => $val) {
                if (in_array($field, $save, true)) {
                    if (is_numeric($val)) {
                        $temp[$year][$field] = number_format($val, 1);
                        //$temp[$year][$field] = ceil($val);
                    } else {
                        $temp[$year][$field] = $val;
                    }
                }
            }
        }

        $res[$this->cspKey]['result'] = $temp;

        return $this->checkResponse($res);
    }

    //天创
    function getFinanceBaseDataTC(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $beginYear = 2021;
        $dataCount = 3;

        if (empty($entName)) {
            return $this->checkResponse([$this->cspKey => [
                'code' => 201,
                'paging' => null,
                'result' => null,
                'msg' => 'entName不能是空',
            ]]);
        }

        $postData = [
            'entName' => $entName,
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $dataCount,
        ];

        $range = FinanceRange::getInstance()->getRange('range_tc');
        $ratio = FinanceRange::getInstance()->getRange('rangeRatio_tc');

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
                $origin[$one->getAttr('ANCHEYEAR')] = obj2Arr($one);
            }
            $obj = new LongXinService();
            $readyReturn = $obj->exprHandle($origin);
            foreach ($readyReturn as $year => $arr) {
                if (empty($arr)) continue;
                foreach ($arr as $field => $val) {
                    if (in_array($field, $range[0], true) && is_numeric($val)) {
                        // !is_numeric($val) ?: $val = $val * 10000;
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($range[1]) - 1, $range[1]);
                    } elseif (in_array($field, $ratio[0], true) && is_numeric($val)) {
                        $readyReturn[$year][$field] = $obj->binaryFind($val, 0, count($ratio[1]) - 1, $ratio[1]);
                    } else {
                        $readyReturn[$year][$field] = $val;
                    }
                }
            }
            krsort($readyReturn);
            $tmp = [];
            for ($i = $beginYear; $i > $beginYear - $dataCount; $i--) {
                $tmp[$i] = $readyReturn[$i];
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

        //留下要的字段
        $save = [
            'ASSGRO',
            'LIAGRO',
            'VENDINC',
            'MAIBUSINC',
            'PROGRO',
            'NETINC',
            'RATGRO',
            'TOTEQU',
            'ASSGRO_yoy',
            'LIAGRO_yoy',
            'VENDINC_yoy',
            'MAIBUSINC_yoy',
            'PROGRO_yoy',
            'NETINC_yoy',
            'RATGRO_yoy',
            'TOTEQU_yoy',
            'SOCNUM',
            'ATOL',
            'DEBTL',
            'C_INTRATESL',
            'ASSGRO_C_INTRATESL',
            'NOR',
            'NPMOMB',
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
        // qudao
        // 渠道（a、b、c、d四种，全选即为'abcd'，默认为'a'）。其中'a'查询的是年报、招聘、等多个公开渠道；'b'、'c'、'd'都是指定账号及 配置查询权限才可以查询，查询的是NonPub数据。

        // lianxitype
        // 类型（1、2、3三种；1手机，2座机，3邮箱；全选即为'123'，默认为空）

        // zhiwei
        // 类型（1企业主/法人，2董事/监事，3总经理/负责人，4销售总监/经理，5市场总监/经理，6会计，7人力资源，全选即为'1234567'，默认为全选）

        // emptycheck
        // 是否进行空号检测（'0'或'1'），默认为'0'不进行空号检测，若是'1'会调用运营商接口进行空号检测，会增加接口响应时长。

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

    //失信被执行人
    function getLessCredit(): bool
    {

        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'version' => 'C1',
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //终本案件
    function getEndCase(): bool
    {

        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'version' => 'C10',
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntDetail($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    // 破产重整核查
    function BankruptcyCheck(): bool
    {

        $entName = trim($this->getRequestData('entName'));

        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new XinDongService())->getBankruptcyCheck(
                $entName
            );
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
//        return $this->writeJson(200,
//            [] , $res, '成功' );
        //return $res;
        return $this->checkResponse($res);
    }

    function getLiquidate(): bool
    {

        $entName = trim($this->getRequestData('entName'));

        $this->csp->add($this->cspKey, function () use ($entName) {
            return CompanyLiquidation::findByName($entName);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->writeJson(200,
            [], $res[$this->cspKey] ? count($res[$this->cspKey]) : 0, '成功');
//        return $this->checkResponse($res);
    }

    function getCancledate(): bool
    {

        $entName = trim($this->getRequestData('entName'));

        $this->csp->add($this->cspKey, function () use ($entName) {
            return CompanyBasic::findCancelDateByCode($entName);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->writeJson(200,
            [], $res[$this->cspKey], '成功');

//        return $this->checkResponse($res);
    }

    function obtainFpInfoNew(): bool
    {
        $isDetail = $this->getRequestData('isDetail');
        $nsrsbh = $this->getRequestData('nsrsbh');
        $startTime = $this->getRequestData('startTime');
        $endTime = $this->getRequestData('endTime');
        $pageNo = $this->getRequestData('pageNo');

        $this->csp->add($this->cspKey, function () use ($isDetail, $nsrsbh, $startTime, $endTime, $pageNo) {
            return (new JinCaiShuKeService())->setCheckRespFlag(true)->obtainFpInfoNew($isDetail, $nsrsbh, $startTime, $endTime, $pageNo);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        $formatedReturnRes = [
            'result' => isset($res[$this->cspKey]['result']['data']['content']) && $res[$this->cspKey]['result']['success'] == true ? $res[$this->cspKey]['result']['data']['content'] : [],
            'code' => $res[$this->cspKey]['result']['success'] == true ? 200 : 210,
            'msg' => $res[$this->cspKey]['result']['msg'],
            'paging' => $res[$this->cspKey]['result']['success'] == true ? ['pageSize' => 1000, 'totalPages' => $res[$this->cspKey]['result']['data']['totalPages']] : [],
        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                '参数' => [
                    '$isDetail' => $isDetail,
                    '$nsrsbh' => $nsrsbh,
                    '$startTime' => $startTime,
                    '$endTime' => $endTime,
                    '$pageNo' => $pageNo,
                ],
                '原始返回' => [
                    'content' => $res[$this->cspKey]['result']['data']['content'],
                    'code' => $res[$this->cspKey]['result']['code'],
                    'msg' => $res[$this->cspKey]['result']['msg'],
                    'success' => $res[$this->cspKey]['result']['success'],
                    'totalPages' => $res[$this->cspKey]['result']['data']['totalPages'],
                ],
                '格式化后的结果' => $formatedReturnRes,
            ], JSON_UNESCAPED_UNICODE), 'info', '发票_授权取数' . date("Ymd") . '.log');

        return $this->writeJson($formatedReturnRes['code'],
            $formatedReturnRes['paging'], $formatedReturnRes['result'], $formatedReturnRes['msg']);
    }

    //除了蚂蚁以外的发过来的企业五要素
    function invEntList(): bool
    {
        $appId = $this->getRequestData('appId');
        $data['entName'] = $this->getRequestData('entName');
        $data['socialCredit'] = $this->getRequestData('socialCredit');
        $data['legalPerson'] = $this->getRequestData('legalPerson');
        $data['idCard'] = $this->getRequestData('idCard');
        $data['phone'] = $this->getRequestData('phone');
        $data['authorized'] = strtoupper($this->getRequestData('authorized'));//有个合作公司不需要信动盖章
        $data['fileUrl'] = $this->getRequestData('fileUrl');//有个合作公司不需要信动盖章

        $data['requestId'] = $this->requestId;

        $userInfo = RequestUserInfo::create()->where('appId', $appId)->get();
        $data['belong'] = $userInfo->getAttr('id');//provide公司id

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
            'pageSize' => $this->getRequestData('pageSize', 10),
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
            'pageSize' => $this->getRequestData('pageSize', 10),
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
            'page' => $this->getRequestData('page'),
            'pageSize' => $this->getRequestData('pageSize'),
            'title' => $this->getRequestData('title'),
            'position' => $this->getRequestData('position'),
            'industry' => $this->getRequestData('industry'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getJobInfo($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, 30);

        return $this->checkResponse($res);
    }

    function getJobDetail(): bool
    {
        $postData = [
            'pid' => $this->getRequestData('pid'),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getJobDetail($postData['pid']);
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
            'version' => trim($this->getRequestData('version', 'A1')),
            'page' => trim($this->getRequestData('page', '1')),
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
            'pageSize' => trim(50),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000523($postData['nsrsbh'], $postData['rwh'], $postData['page'], $postData['pageSize']);
        });

        $res = CspService::getInstance()->exec($this->csp, 60);

        return $this->checkResponse($res);
    }

    function get24Month(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $postData = [
            'nsrsbh' => $nsrsbh,
        ];
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->get24Month($postData['nsrsbh']);
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
        $entName = $this->getRequestData('entName');
        if (!$entName) {
            return $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $entNames = [];
        $entNames[$entName] = $entName;

        // 如果包含中文或者英文括号  需要中英文括号都查下
        if (
            strpos($entName, '）') !== false &&
            strpos($entName, '（') !== false
        ) {
            $newEntName = str_replace(['（', '）'], ['(', ')'], $entName);
            $entNames[$newEntName] = $newEntName;
        }

        if (
            strpos($entName, ')') !== false &&
            strpos($entName, '(') !== false
        ) {
            $newEntName = str_replace(['(', ')'], ['（', '）'], $entName);
            $entNames[$newEntName] = $newEntName;
        }

        $this->csp->add($this->cspKey, function () use ($entNames) {
            return (new XinDongService())
                ->getEntInfoByName($entNames);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        // $res = (new XinDongService())
        //     ->getEntInfoByName($entNames);

        return $this->checkResponse($res);

    }

    function matchCompanyByFuzzyName(): bool
    {
        $entName = $this->getRequestData('entName');
        if (!$entName) {
            return $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $entNames = [];
        $entNames[$entName] = $entName;

        // 如果包含中文或者英文括号  需要中英文括号都查下
        if (
            strpos($entName, '）') !== false &&
            strpos($entName, '（') !== false
        ) {
            $newEntName = str_replace(['（', '）'], ['(', ')'], $entName);
            $entNames[$newEntName] = $newEntName;
        }

        if (
            strpos($entName, ')') !== false &&
            strpos($entName, '(') !== false
        ) {
            $newEntName = str_replace(['(', ')'], ['（', '）'], $entName);
            $entNames[$newEntName] = $newEntName;
        }

        $this->csp->add($this->cspKey, function () use ($entNames) {
            return (new XinDongService())
                ->getEntInfoByName($entNames);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        // $res = (new XinDongService())
        //     ->getEntInfoByName($entNames);

        return $this->checkResponse($res);

    }

    //非注册地址
    function getEntAddress(): bool
    {
        $postData = [
            'entName' => trim($this->getRequestData('entName')),
            'page' => trim($this->getRequestData('page', '1')),
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getEntAddress($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //是否纳税一般人
    function getEnterprise(): bool
    {
        $code = trim($this->getRequestData('code'));

        $this->csp->add($this->cspKey, function () use ($code) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getEnterprise($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    function testCsp(): bool
    {
        $timeStart = microtime(true);

        $entName = $this->getRequestData('entName');
        if (!$entName) {
            return $this->writeJson(201, null, null, '参数缺失(企业名称)');
        }

        $csp = new \EasySwoole\Component\Csp();

        // for ($i=0; $i < 7; $i++) { 
        //     $csp->add('t'.$i, function () use ($i,$entName) {

        //         $sql = "SELECT
        //                 id,`name`
        //             FROM
        //                 `company_name_$i`
        //             WHERE
        //                 MATCH(`name`) AGAINST(
        //                 '$entName'    IN NATURAL LANGUAGE MODE
        //                 )  
        //             LIMIT 1";
        //         $timeStart2 = microtime(true);   
        //         $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        //         $timeEnd2 = microtime(true); 
        //         $execution_time11 = ($timeEnd2 - $timeStart2); 

        //         return  [
        //             $list,
        //             $sql,
        //             $execution_time11
        //         ];
        //     }); 
        // } 
        for ($i = 0; $i < 7; $i++) {
            $csp->add('t_' . $i, function () use ($i, $entName) {

                $arr = preg_split('/(?<!^)(?!$)/u', $entName);
                $matchStr = "";
                if ($arr[0] && $arr[1]) {
                    $matchStr .= '+' . $arr[0] . $arr[1];
                }
                if ($arr[2] && $arr[3]) {
                    $matchStr .= '+' . $arr[2] . $arr[3];
                }
                if ($arr[4] && $arr[5]) {
                    $matchStr .= '+' . $arr[4] . $arr[5];
                }
                if ($arr[6] && $arr[7]) {
                    $matchStr .= '+' . $arr[6] . $arr[7];
                }
                if ($arr[8] && $arr[9]) {
                    $matchStr .= '+' . $arr[8] . $arr[9];
                }

                $sql = "SELECT
                        id,`name`
                    FROM
                        `company_name_$i`
                    WHERE
                        MATCH(`name`) AGAINST(
                        '$matchStr'    IN BOOLEAN MODE
                        )  
                    LIMIT 1";
                $timeStart2 = microtime(true);
                $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
                $timeEnd2 = microtime(true);
                $execution_time11 = ($timeEnd2 - $timeStart2);

                return [
                    $list,
                    $sql,
                    $execution_time11
                ];
            });
        }
        $csp->add('t00', function () use ($entName) {
            $sql = "SELECT
                    id,`name`
                FROM
                    `company`
                WHERE
                     `name` = '$entName'
                LIMIT 1";
            $timeStart2 = microtime(true);
            $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
            $timeEnd2 = microtime(true);
            $execution_time11 = ($timeEnd2 - $timeStart2);
            return [
                $list,
                $sql,
                $execution_time11
            ];
        });
        $res = ($csp->exec(3.5));

        CommonService::getInstance()->log4PHP('testCsp' .
            json_encode(
                $res
            ));


        $timeEnd = microtime(true);
        $execution_time1 = ($timeEnd - $timeStart);
        return $this->writeJson(200,
            [

            ]
            , [
                'Time' => 'Total Execution Time:' . $execution_time1 . ' 秒  |',
                'data' => $res,
            ], '成功', true, []);
        // return $this->checkResponse($newres);

    }

    //淘数 龙盾 历史沿革
    function getHistoricalEvolution(): bool
    {
        $entName = trim($this->getRequestData('entName'));
        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getHistoricalEvolution($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    function createEntReportE(): bool
    {
        $data = [
            'entName' => trim($this->getRequestData('entName')),
            'appId' => trim($this->getRequestData('appId')),
            'email' => trim($this->getRequestData('email')),
        ];
        $this->csp->add($this->cspKey, function () use ($data) {
            return (new ReportWordService())->createEasy($data);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    function createEntReportD(): bool
    {
        $data = [
            'entName' => trim($this->getRequestData('entName')),
            'code' => trim($this->getRequestData('code')),
            'appId' => trim($this->getRequestData('appId')),
            'email' => trim($this->getRequestData('email')),
        ];
        $this->csp->add($this->cspKey, function () use ($data) {
            return (new ReportWordService())->createDeep($data);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //企业基本信息
    function getEntMarketInfo(): bool
    {
        $code = $this->getRequestData('code', '');
        if (empty($code)) {
            return $this->writeJson(201, null, null, '参数缺失(统一社会信用代码)');
        }
        $this->csp->add($this->cspKey, function () use ($code) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getEntMarketInfo($code);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        CommonService::getInstance()->log4PHP($res, 'info', 'getEntMarketInfo');
        return $this->checkResponse($res);
    }

    //清算信息接口
    function getEntLiquidation(): bool
    {
        $code = $this->getRequestData('code', '');
        if (empty($code)) {
            return $this->writeJson(201, null, null, '参数缺失(统一社会信用代码)');
        }
        $this->csp->add($this->cspKey, function () use ($code) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getEntLiquidation($code);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }


    function fuzzyMatchEntName(): bool
    {
        //
        $name = trim($this->getRequestData('name'));
        if (empty($name)) {
            return $this->writeJson(201, null, [], '参数错误(' . $name . ')', true, []);
        }

        $datas = XinDongService::fuzzyMatchEntName($name, 3);
        $name1 = $datas[0]['_source']['ENTNAME'];
        $name2 = $datas[1]['_source']['ENTNAME'];
        $name3 = $datas[2]['_source']['ENTNAME'];
        return $this->writeJson(200, null, [
            $name1,
            $name2,
            $name3,
        ], null, true, []);

    }

    function getCncaRzGltx_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCncaRzGltx_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-行政处罚
    function getCaseAll_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCaseAll_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-抽查检查信息
    function getCaseCheck_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCaseCheck_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-严重违法失信
    function getCaseYzwfsx_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCaseYzwfsx_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-经营异常
    function getCompanyAbnormity_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyAbnormity_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //企业基本信息
    function getCompanyBasic_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyBasic_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报-主表
    function getCompanyAr_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyAr_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报股权变更
    function getCompanyArAlterstockinfo_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArAlterstockinfo_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报-对外投资
    function getCompanyArForinvestment_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArForinvestment_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报-对外提供保证担保信息
    function getCompanyArForguaranteeinfo_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArForguaranteeinfo_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报-资产
    function getCompanyArAsset_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArAsset_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报出资
    function getCompanyArCapital_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArCapital_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报出资
    function getCompanyArModify_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 10);
        $code = $this->getRequestData('code', '');
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($code, $entName, $page, $pageSize) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArModify_h($code, $entName, $page, $pageSize);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    function getCompanyArSocialfee_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArSocialfee_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-年报-网站或网店信息
    function getCompanyArWebsiteinfo_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyArWebsiteinfo_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-注吊销信息
    function getCompanyCancelInfo_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyCancelInfo_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-分支机构
    function getCompanyFiliation_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyFiliation_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //大数据-曾用名表
    function getCompanyHistoryName_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyHistoryName_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-企业股东
    function getCompanyInv_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyInv_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-企业主要人员
    function getCompanyManager_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyManager_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-行政许可
    function getCompanyCertificate_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyCertificate_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //大数据-股权轨迹表
    function getCompanyHistoryInv_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyHistoryInv_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //大数据-历史高管表
    function getCompanyHistoryManager_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyHistoryManager_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-对外投资信息
    function getCompanyInvestment_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyInvestment_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-知识产权出质
    function getCompanyIpr_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyIpr_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-知识产权出质-变更信息
    function getCompanyIprChange_h(): bool
    {
        $id = $this->getRequestData('id', '');

        if (empty($id)) {
            return $this->writeJson(201, null, null, '参数(id)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($id) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyIprChange_h($id);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-清算信息
    function getCompanyLiquidation_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyLiquidation_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-公司变更信息
    function getCompanyModify_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyModify_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-动产抵押
    function getCompanyMort_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyMort_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-动产抵押-变更信息
    function getCompanyMortChange_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyMortChange_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-动产抵押-抵押物信息
    function getCompanyMortPawn_p(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyMortPawn_p($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-动产抵押-抵押权人信息
    function getCompanyMortPeople_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyMortPeople_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //工商-股权质押
    function getCompanyStockImpawn_h(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $postData = [
            'entName' => $entName,
            'code' => $code
        ];
        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCompanyStockImpawn_h($postData);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //上市公司指标信息
    function getListedIndexLLJ(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');

        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $code
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getListedIndex($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //获取商品码
    function getCommodityCode(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $code = $this->getRequestData('code', '');
        $page = $this->getRequestData('page', 1);

        if (empty($entName) && empty($code)) {
            return $this->writeJson(201, null, null, '参数(entName,code)不可以都为空');
        }

        $postData = [
            'entName' => $entName,
            'code' => $code,
            'page' => $page,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getCommodityCode($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //鲸准 投资事件
    function investmentList(): bool
    {
        $entName = $this->getRequestData('entName', '');

        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new JingZhunService())
                ->setCheckRespFlag(true)
                ->investmentList($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //鲸准 公司融资事件
    function enterpriseList(): bool
    {
        $entName = $this->getRequestData('entName', '');

        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }
        dingAlarm('鲸准 公司融资事件', ['$entName' => $entName]);
        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new JingZhunService())
                ->setCheckRespFlag(true)
                ->enterpriseList($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //鲸准 企业搜索
    function searchComs(): bool
    {
        $entName = $this->getRequestData('entName', '');

        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }
        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new JingZhunService())
                ->setCheckRespFlag(true)
                ->searchComs($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //招投标
    function getBidInfo(): bool
    {
        $entName = $this->getRequestData('entName', '');
        $node = $this->getRequestData('node', 'G1');
        $page = $this->getRequestData('page', 1);
        $pageSize = $this->getRequestData('pageSize', 300);

        $node === 'G1-2' ?: $node = 'G1';

        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }

        $this->csp->add($this->cspKey, function () use ($entName, $node, $page, $pageSize) {
            return (new LongXinService())
                ->setCheckRespFlag(true)
                ->getBidInfo([
                    'entName' => $entName,
                    'node' => $node,
                    'page' => $page,
                    'pageSize' => $pageSize
                ]);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    //二次特征分数
    function getFeatures()
    {
        $entName = $this->getRequestData('entName', '');
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }

        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new XinDongService())
                ->setCheckRespFlag(true)
                ->getFeatures($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    //获取企业的风险分
    function getFengXian()
    {
        $entName = $this->getRequestData('entName', '');
        if (empty($entName)) {
            return $this->writeJson(201, null, null, '参数entName不可以都为空');
        }

        $this->csp->add($this->cspKey, function () use ($entName) {
            return (new FenShuService())
                ->setCheckRespFlag(true)
                ->getFengXian($entName);
        });
        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }
}