<?php

namespace App\HttpController\Business\Admin\Api;

use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\CreateSessionHandler;
use EasySwoole\Session\Session;

class PApiController extends ApiBase
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        CreateSessionHandler::getInstance()->check($this->request(), $this->response());

        $isLogin = Session::getInstance()->get('isLogin');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getApiList()
    {
        $res = RequestApiInfo::create()->all();

        return $this->writeJson(200,null,$res);
    }





}