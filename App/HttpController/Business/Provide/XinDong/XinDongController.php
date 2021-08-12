<?php

namespace App\HttpController\Business\Provide\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\FinanceRange;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\SmsService;
use App\HttpController\Service\TaoShu\TaoShuService;
use App\HttpController\Service\XinDong\XinDongService;
use Carbon\Carbon;

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

    function checkResponse($res)
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
    function sendSms()
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
    function getProductStandard()
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
    function getRegisterInfo()
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
    function getFinanceBaseData()
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

        //周伯通
        if ($this->userId === 35) {
            if ($beginYear === 2020 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2019 && $dataCount <= 2) {
                $a = null;
            } elseif ($beginYear === 2018 && $dataCount === 1) {
                $a = null;
            } else {
                return $this->writeJson(201, null, null, '参数错误');
            }
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

    //连续n年基数+计算结果
    function getFinanceCalData()
    {
        $beginYear = $this->getRequestData('year', '');

        $postData = [
            'entName' => $this->getRequestData('entName', ''),
            'code' => $this->getRequestData('code', ''),
            'beginYear' => $beginYear,
            'dataCount' => $this->getRequestData('dataCount', 3),//取最近几年的
        ];

        $toRange = false;

        if (is_numeric($beginYear) && $beginYear >= 2010 && $beginYear <= date('Y') - 1) {
            $this->csp->add($this->cspKey, function () use ($postData, $toRange) {
                return (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getFinanceData($postData, $toRange);
            });
            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        } else {
            $res = [];
            $this->responseMsg = 'year参数错误';
        }

        return $this->checkResponse($res);
    }

    //单年基础数区间 含 并表判断
    function getFinanceBaseMergeData()
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
    function getFinanceCalMergeData()
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
        $entName = $this->getRequestData('entName');
        $code = $this->getRequestData('code');

        if (empty($entName) || empty($code)) {
            $res = [];
            $this->responseMsg = '参数不能是空';
        } else {
            $postData['basic_entname'] = "any:{$entName}";
            $postData['basic_uniscid'] = "any:{$code}";

            $this->csp->add($this->cspKey, function () use ($postData, $entName, $code) {
                $superSearch = (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->superSearch($postData);
                $finance = (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getFinanceData([
                        'entName' => $entName,
                        'code' => $code,
                        'beginYear' => date('Y') - 1,
                        'dataCount' => 3,
                    ], false);
                $data = [
                    'code' => 200,
                    'paging' => null,
                    'result' => [
                        'nic_id' => [],
                        'year' => null,
                        'VENDINC' => null,
                        'ASSGRO' => null,
                        'SOCNUM' => null,
                    ],
                    'msg' => null,
                ];
                if ($superSearch['code'] === 200 && !empty($superSearch['result'])) {
                    $nic_id = explode('-', current($superSearch['result'])['nic_id']);
                    $nic_id = array_filter($nic_id);
                    $data['result']['nic_id'] = $nic_id;
                }
                if ($finance['code'] === 200 && !empty($finance['result'])) {
                    foreach ($finance['result'] as $year => $val) {
                        if ($year - 0 === 2020) continue;
                        if (!empty($val)) {
                            $data['result']['year'] = $year - 0;
                            $data['result']['VENDINC'] = is_numeric($val['VENDINC']) ? $val['VENDINC'] - 0 : null;
                            $data['result']['ASSGRO'] = is_numeric($val['ASSGRO']) ? $val['ASSGRO'] - 0 : null;
                            $data['result']['SOCNUM'] = is_numeric($val['SOCNUM']) ? $val['SOCNUM'] - 0 : null;
                            break;
                        }
                    }
                }
                return $data;
            });

            $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        }

        return $this->checkResponse($res);
    }

    //物流搜索
    function logisticsSearch()
    {

    }

    //企业联系方式
    function getEntLianXi()
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
                ->getEntLianXi($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}