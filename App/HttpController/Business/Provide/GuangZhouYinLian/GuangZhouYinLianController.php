<?php

namespace App\HttpController\Business\Provide\GuangZhouYinLian;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\FaYanYuan\GuangZhouYinLianService;

class GuangZhouYinLianController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    function queryVehicleCount(): bool
    {
        dingAlarm('车辆数量查询',['rukou'=>1]);
        $postData = [];
        $this->csp->add($this->cspKey, function () use ($postData) {
            dingAlarm('车辆数量查询',['rukou'=>2]);
            return (new GuangZhouYinLianService())->queryVehicleCount($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);

    }
    function checkResponse($res): bool
    {
        if (empty($res[$this->cspKey])) {
            $this->responseCode = $res['code'] ?? 500;
            $this->responsePaging = $res['paging'] ?? null;
            $this->responseData = $res['result'] ?? null;
            $this->spendMoney = 0;
            $this->responseMsg = $res['msg'] ?? '请求超时';
        } else {
            $this->responseCode = $res[$this->cspKey]['code'];
            $this->responsePaging = $res[$this->cspKey]['paging'];
            $this->responseData = $res[$this->cspKey]['result'];
            $this->responseMsg = $res[$this->cspKey]['msg'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }
}