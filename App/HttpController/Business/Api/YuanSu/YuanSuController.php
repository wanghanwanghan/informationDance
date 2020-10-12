<?php

namespace App\HttpController\Business\Api\YuanSu;

use App\HttpController\Service\YuanSu\YuanSuService;

class YuanSuController extends YuanSuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //三要素
    function three()
    {
        $params=[
            'entName'=>'阿里巴巴（中国）网络技术有限公司',
            'creditCode'=>'',
        ];

        (new YuanSuService())->getList('https://api.elecredit.com/saic/enterprise/deep',$params);
    }


}