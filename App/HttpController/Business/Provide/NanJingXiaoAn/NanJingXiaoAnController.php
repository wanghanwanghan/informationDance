<?php

namespace App\HttpController\Business\Provide\NanJingXiaoAn;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\NanJingXiaoAn\NanJingXiaoAnService;

class NanJingXiaoAnController extends ProvideBase
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

    //三网运营商二要素实名认证
    function generalMobileInfo(): bool
    {
        $name = $this->getRequestData('name');
        $mobile = $this->getRequestData('mobile');

        $postData = [
            'name' => $name,
            'mobile' => $mobile,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new NanJingXiaoAnService())->generalMobileInfo(
                $postData['name'],
                $postData['mobile']
            );
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}



