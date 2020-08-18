<?php

namespace App\HttpController\Business;

use App\HttpController\Index;

class BusinessBase extends Index
{
    //继承这个主要是为了可以writeJson

    //也是为了onRequest
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //重写writeJson
    function writeJson($statusCode=200,$paging=null,$result=null,$msg=null)
    {
        if (!$this->response()->isEndResponse())
        {
            $data=[
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $result,
                'msg' => $msg
            ];

            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);

            return true;

        }else
        {
            return false;
        }
    }

    //check token
    private function checkToken(): ?bool
    {
        //通过返回true，不通过返回false

        //token验证
        $token=$this->request()->getHeader('authorization');

        $token=(current($token));

        return true;
    }
}
