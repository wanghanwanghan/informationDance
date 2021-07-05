<?php

namespace App\HttpController\Business\Provide\YunMaTong;

use App\Csp\Service\CspService;
use App\HttpController\Business\Provide\ProvideBase;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\YunMaTong\YunMaTongService;

class YunMaTongController extends ProvideBase
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

    function bankCardInfo()
    {
        $arr['bankcard'] = $this->getRequestData('bankcard');
        $arr['realname'] = $this->getRequestData('realname');
        $arr['idcard'] = $this->getRequestData('idcard');
        $arr['mobile'] = $this->getRequestData('mobile');

        if (empty($arr['bankcard'])) {
            return $this->writeJson(200, null, null, '银行卡号不能是空');
        }

        if (empty($arr['realname'])) {
            //return $this->writeJson(200, null, null, '姓名不能是空');
        }

        if (empty($arr['idcard'])) {
            //return $this->writeJson(200, null, null, '身份证号不能是空');
        }

        if (empty($arr['mobile'])) {
            //return $this->writeJson(200, null, null, '手机号不能是空');
        }

        $this->csp->add($this->cspKey, function () use ($arr) {
            return (new YunMaTongService())->bankCardInfo($arr);
        });

        $res = CspService::getInstance()->exec($this->csp, $this->cspTimeout);

        return $this->checkResponse($res);
    }


}