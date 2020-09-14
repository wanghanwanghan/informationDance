<?php

namespace App\HttpController\Business\Api\TaoShu;

use App\HttpController\Service\TaoShu\TaoShuService;
use wanghanwanghan\someUtils\control;

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

    //检验企查查返回值，并给客户计费
    private function checkResponse($res)
    {
        if (isset($res['PAGEINFO']) && isset($res['PAGEINFO']['TOTAL_COUNT']) && isset($res['PAGEINFO']['TOTAL_PAGE']) && isset($res['PAGEINFO']['CURRENT_PAGE']))
        {
            $res['Paging']=[
                'page'=>$res['PAGEINFO']['CURRENT_PAGE'],
                'pageSize'=>null,
                'total'=>$res['PAGEINFO']['TOTAL_COUNT'],
                'totalPage'=>$res['PAGEINFO']['TOTAL_PAGE'],
            ];

        }else
        {
            $res['Paging']=null;
        }

        if (isset($res['coHttpErr'])) return $this->writeJson(500,$res['Paging'],[],'co请求错误');

        $res['ISUSUAL'] == '1' ? $res['code'] = 200 : $res['code'] = 600;

        //拿返回结果
        isset($res['RESULTDATA']) ? $res['Result'] = $res['RESULTDATA'] : $res['Result'] = [];

        return $this->writeJson($res['code'],$res['Paging'],$res['Result'],null);
    }

    //企业基本信息
    function getRegisterInfo()
    {
        $entName=$this->request()->getRequestParam('entName') ?? '';

        $postData=['entName'=>$entName];

        $res=(new TaoShuService())->post($postData,__FUNCTION__);

        return $this->checkResponse($res);
    }














}