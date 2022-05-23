<?php

namespace App\HttpController\Business\Api\GuangZhouYinLian;


use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuangZhouYinLian\GuangZhouYinLianService;

class GuangZhouYinLianController  extends GuangZhouYinLianBase
{

    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getCarsInsurance(){
        $socialCredit             = $this->getRequestData('socialCredit');
        $postData         = [
            'socialCredit' => $socialCredit,
        ];

        list($paging,$tmp) = (new GuangZhouYinLianService())->setCheckRespFlag(true)->getCarsInsurance($postData);
        CommonService::getInstance()->log4PHP($tmp,'info','getCarsInsurance');

        return $this->writeJson(200, $paging, $tmp, '查询成功');
    }

}