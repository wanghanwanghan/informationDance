<?php

namespace App\HttpController;

use EasySwoole\Http\AbstractInterface\Controller;
use wanghanwanghan\someUtils\control;

class Index extends Controller
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //链接池系列抛出异常
    function writeErr(\Throwable $e,$which='comm'): bool
    {
        //给用户看的
        $this->writeJson(9527,[],$which.'错误');

        $logFileName=$which.'.log.'.date('Ymd',time());

        //给程序员看的
        $file=$e->getFile();
        $line=$e->getLine();
        $msg=$e->getMessage();

        $content="[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content,LOG_PATH,'info',$logFileName);
    }















    function index() {}
}