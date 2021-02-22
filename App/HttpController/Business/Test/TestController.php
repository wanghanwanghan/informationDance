<?php

namespace App\HttpController\Business\Test;

use App\HttpController\Business\BusinessBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\QianQi\QianQiService;

class TestController extends BusinessBase
{
    function onRequest(?string $action): ?bool
    {
        return true;
    }

    function caiwu()
    {
        $entList = $this->request()->getRequestParam('entList') ?? '';

        $entList = str_replace('，', ',', $entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        $entList = explode(',', $entList);

        $entList = array_filter($entList);

        if (empty($entList)) return $this->writeJson(201, null, null, '公司名称不能是空');

        foreach ($entList as $entName) {

            $entName = trim($entName);

            $res = (new QianQiService())
                ->setCheckRespFlag(true)
                ->getThreeYears(['entName' => $entName]);

            CommonService::getInstance()->log4PHP($res);
        }

        return $this->writeJson();
    }

}