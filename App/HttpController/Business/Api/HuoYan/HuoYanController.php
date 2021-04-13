<?php

namespace App\HttpController\Business\Api\HuoYan;

use App\HttpController\Service\HuoYan\HuoYanService;

class HuoYanController extends HuoYanBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    private function checkResponse($res)
    {
        return $this->writeJson($res);
    }

    //仿企名片
    function getData()
    {
        $res = (new HuoYanService())->setCheckRespFlag(true)->getData([]);

        return $this->checkResponse($res);
    }


}