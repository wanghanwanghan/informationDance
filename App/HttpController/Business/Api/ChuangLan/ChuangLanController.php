<?php

namespace App\HttpController\Business\Provide\ChuangLan;


use App\HttpController\Business\Api\ChuangLan\ChuangLanBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;

class ChuangLanController extends ChuangLanBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getCheckPhoneStatus(){
        $mobiles = $this->getRequestData('mobiles');
        if (empty($mobiles))
            return $this->writeJson(201, null, null, 'mobiles参数不能是空');
        $postData = [
            'mobiles' => $mobiles,
        ];
        $res = (new ChuangLanService())->getCheckPhoneStatus($postData);
        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }
}