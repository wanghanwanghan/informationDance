<?php

namespace App\HttpController\Business\Provide\JinCai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use Carbon\Carbon;

class JinCaiController extends ProvideBase
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
            $this->responseMsg = '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }

    // 无盘 创建任务
    function addTask(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $province = $this->getRequestData('province');//北京
        $city = $this->getRequestData('city');//北京

        $kprqq = $sbrqq = Carbon::now()->subMonths(36)->startOfMonth()->timestamp;
        $kprqz = $sbrqz = Carbon::now()->subMonths(1)->startOfMonth()->timestamp;

        // 拼task请求参数
        $ywBody = [
            'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
            'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
            'nsrsbh' => $nsrsbh,// 纳税人识别号
        ];

        $sbrBody = [
            'sbrqq' => date('Y-m-d', $sbrqq),// 申报日期起
            'sbrqz' => date('Y-m-d', $sbrqz),// 申报日期止
            'nsrsbh' => $nsrsbh,// 纳税人识别号
        ];

        $this->csp->add($this->cspKey, function () use ($nsrsbh, $province, $city, $ywBody, $sbrBody) {
            $info = (new JinCaiShuKeService())->addTaskSbr($nsrsbh, $province, $city, $sbrBody);
            CommonService::getInstance()->log4PHP($info, 'info', 'addTask');
            return (new JinCaiShuKeService())->addTaskNew($nsrsbh, $province, $city, $ywBody);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        CommonService::getInstance()->log4PHP($res, 'info', 'addTask');

        return $this->checkResponse($res);
    }

    // 无盘 取票
    function obtainFpInfoNew(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $kprqq = $this->getRequestData('kprqq');//Y-m-d
        $kprqz = $this->getRequestData('kprqz');//Y-m-d
        $page = $this->getRequestData('page');

        $this->csp->add($this->cspKey, function () use ($nsrsbh, $kprqq, $kprqz, $page) {
            return (new JinCaiShuKeService())->obtainFpInfoNew(
                true, $nsrsbh, $kprqq, $kprqz, $page
            );
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    // 获取流水号集合
    function obtainFpTraceNoList(): bool
    {
        $traceNo = $this->getRequestData('username');

        $this->csp->add($this->cspKey, function () use ($traceNo) {
            return (new JinCaiShuKeService())->obtainFpTraceNoList($traceNo);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    // 发票文件
    function obtainFpFile(): bool
    {
        $traceNo = $this->getRequestData('traceNo');

        $this->csp->add($this->cspKey, function () use ($traceNo) {
            return (new JinCaiShuKeService())->obtainFpFile($traceNo);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

    // 无盘 取税
    function obtainAllTaxesInfo(): bool
    {
        $nsrsbh = $this->getRequestData('nsrsbh');
        $skssqq = $this->getRequestData('skssqq');//Y-m-d
        $skssqz = $this->getRequestData('skssqz');//Y-m-d

        $this->csp->add($this->cspKey, function () use ($nsrsbh, $skssqq, $skssqz) {
            return (new JinCaiShuKeService())->obtainAllTaxesInfo(
                $nsrsbh, $skssqq, $skssqz
            );
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }

}