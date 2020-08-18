<?php

namespace App\HttpController\Business\Api\QiChaCha;

use App\HttpController\Service\QiChaCha\QiChaChaService;
use App\HttpController\Service\TaoShu\TaoShuService;
use wanghanwanghan\someUtils\control;

class QiChaChaController extends QiChaChaBase
{
    private $baseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->baseUrl=\Yaconf::get('qichacha.baseUrl');

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
        if (isset($res['Paging']) && !empty($res['Paging']))
        {
            $res['Paging']=control::changeArrKey($res['Paging'],[
                'PageSize'=>'pageSize',
                'PageIndex'=>'page',
                'TotalRecords'=>'total'
            ]);
        }else
        {
            $res['Paging']=null;
        }

        return $this->writeJson($res['Status'],$res['Paging'],$res['Result'],$res['Message']);
    }

    //模糊搜索企业列表
    function getEntList()
    {
        $entName=$this->request()->getRequestParam('entName');
        $page=$this->request()->getRequestParam('page') ?? 1;
        $pageSize=$this->request()->getRequestParam('pageSize') ?? 10;

        $postData=[
            'keyWord'=>$entName,
            'pageIndex'=>$page,
            'pageSize'=>$pageSize,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIV4/Search',$postData);

        return $this->checkResponse($res);
    }

    //律所及其他特殊基本信息
    function getSpecialEntDetails()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'searchKey'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIOther/GetDetails',$postData);

        return $this->checkResponse($res);
    }

    //企业类型查询
    function getEntType()
    {
        $entName=$this->request()->getRequestParam('entName');

        $postData=[
            'searchKey'=>$entName,
        ];

        $res=(new QiChaChaService())->get($this->baseUrl.'ECIEntType/GetEntType',$postData);

        return $this->checkResponse($res);
    }





}