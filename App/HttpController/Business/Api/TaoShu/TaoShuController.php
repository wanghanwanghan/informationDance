<?php

namespace App\HttpController\Business\Api\TaoShu;

use App\HttpController\Service\TaoShu\TaoShuService;

class TaoShuController extends TaoShuBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //企业基本信息
    function getRegisterInfo()
    {
        $entName=$this->request()->getRequestParam('entName') ?? '';

        $postData=['searchKey'=>$entName];

        $res=(new TaoShuService())->post($postData,__FUNCTION__);

        var_export($res);

        //return $this->checkResponse($res);







    }














}