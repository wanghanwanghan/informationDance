<?php

namespace App\HttpController\Business\Api\ZhiChi;

use App\HttpController\Business\Api\ChuangLan\ChuangLanBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\ZhiChi\ZhiChiService;

class ZhiChiController extends ChuangLanBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function directUrl(){
        $res = (new ZhiChiService())->directUrl();
        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
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