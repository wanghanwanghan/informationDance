<?php

namespace App\HttpController\Service;

use wanghanwanghan\someUtils\control;

class ServiceBase
{
    function onNewService(): ?bool
    {
        return true;
    }

    function writeErr(\Exception $e,$which=__CLASS__,$type='info'): bool
    {
        $logFileName=$which.'.log.'.date('Ymd',time());

        //给程序员看的
        $file=$e->getFile();
        $line=$e->getLine();
        $msg=$e->getMessage();

        $content="[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content,LOG_PATH,$type,$logFileName);
    }






}
