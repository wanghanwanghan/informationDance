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
}