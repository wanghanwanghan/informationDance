<?php

namespace App\HttpController\Business\Api\FaHai;

use App\HttpController\Service\FaHai\FaHaiService;
use EasySwoole\Pool\Manager;

class FaHaiController extends FaHaiBase
{
    private $baseUrl;

    function onRequest(?string $action): ?bool
    {
        $this->baseUrl=\Yaconf::get('fahai.baseUrl');

        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function index()
    {
        $url=$this->baseUrl.'sifa';

        $body['doc_type']=$this->request()->getRequestParam('doc_type');
        $body['keyword']=$this->request()->getRequestParam('keyword');
        $body['pageno']=1;
        $body['range']=10;

        //$res=(new FaHaiService())->getList($url,$body);

        $obj=Manager::getInstance()->get('project')->getObj();

        Manager::getInstance()->get('project')->recycleObj($obj);

        $res=[321];





        $this->writeJson(200,$res);

        return true;
    }
}