<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Dzq;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Service\DianZiqian\DianZiQianService;

class DzqController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    public function accountInfo(){
        $res = (new DianZiQianService())->accountInfo();
        return $this->writeJson($res['code'], null, $res, '成功');
    }
    public function costRecord(){
        $freezeDate = $this->getRequestData('freezeDate');
        if(empty($freezeDate)){
            return $this->writeJson(201, null, [], 'freezeDate不能为空');
        }
        $postData = ['freezeDate'=>$freezeDate];
        $res = (new DianZiQianService())->costRecord($postData);
        return $this->writeJson($res['code'], null, $res, '成功');
    }

}