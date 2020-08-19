<?php

namespace App\HttpController\Business;

use App\HttpController\Index;
use wanghanwanghan\someUtils\control;

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

    //链接池系列抛出异常
    function writeErr(\Throwable $e,$which='comm'): bool
    {
        //给用户看的
        $this->writeJson(9527,null,null,$which.'错误');

        $logFileName=$which.'.log.'.date('Ymd',time());

        //给程序员看的
        $file=$e->getFile();
        $line=$e->getLine();
        $msg=$e->getMessage();

        $content="[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content,LOG_PATH,'info',$logFileName);
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
