<?php

namespace App\HttpController\Business\Provide\XinDong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Models\Api\TyyPendingEnt;

class Tyy extends ProvideBase
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

    //天翼云传过来的企业
    function pendingEnt(): bool
    {
        $entNameString = $this->getRequestData('entNameString');
        $remark = $this->getRequestData('remark');
        $type = $this->getRequestData('type');

        $this->csp->add($this->cspKey, function () use ($entNameString, $remark, $type) {
            if (!empty($entNameString)) {
                $entName_arr = explode('|', trim($entNameString));
                $insert = [];
                foreach ($entName_arr as $one) {
                    $insert[] = [
                        'entname' => trim($one),
                        'remark' => trim($remark),
                        'type' => trim(strtolower($type))
                    ];
                }
                try {
                    TyyPendingEnt::create()
                        ->data($insert)
                        ->saveAll($insert, false, false);
                } catch (\Throwable $exception) {

                }
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


}