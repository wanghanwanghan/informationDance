<?php

namespace App\HttpController\Business\Provide\ChuangLan;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;

class ChuangLanController extends ProvideBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function getCheckPhoneStatus()
    {
        $mobiles = $this->getRequestData('mobiles');
        if (empty($mobiles))
            return $this->writeJson(201, null, null, 'mobiles参数不能是空');

        $postData = [
            'mobiles' => $mobiles,
        ];

        $this->csp->add($this->cspKey, function () use ($postData) {
            return (new ChuangLanService())
                ->setCheckRespFlag(true)
                ->getCheckPhoneStatus($postData);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);
        return $this->checkResponse($res);
    }

    function checkResponse($res)
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
            $this->responseData = $res[$this->cspKey]['data'];
            $this->responseMsg = $res[$this->cspKey]['message'];

            $res[$this->cspKey]['code'] === 200 ?: $this->spendMoney = 0;
        }

        return true;
    }
}