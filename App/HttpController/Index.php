<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;

class Index extends Controller
{
    public $userToken;

    function onRequest(?string $action): ?bool
    {
        $token=$this->request()->getHeader('authorization');
        $this->userToken=(current($token));

        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function index() {}
}