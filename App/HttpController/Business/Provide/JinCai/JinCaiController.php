<?php

namespace App\HttpController\Business\Provide\JinCai;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
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

        $kprqq = Carbon::now()->subMonths(36)->startOfMonth()->timestamp;
        $kprqz = Carbon::now()->subMonths(1)->startOfMonth()->timestamp;

        // 拼task请求参数
        $ywBody = [
            'kprqq' => date('Y-m-d', $kprqq),// 开票日期起
            'kprqz' => date('Y-m-d', $kprqz),// 开票日期止
            'nsrsbh' => $nsrsbh,// 纳税人识别号
        ];

        $this->csp->add($this->cspKey, function () use ($nsrsbh, $province, $city, $ywBody) {
            return (new JinCaiShuKeService())->addTaskNew($nsrsbh, $province, $city, $ywBody);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

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

}