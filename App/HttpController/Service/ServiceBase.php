<?php

namespace App\HttpController\Service;

use wanghanwanghan\someUtils\control;

class ServiceBase
{
    //各个service在返回结果之前进行返回值检测
    public $checkRespFlag = false;

    function __construct()
    {
        return true;
    }

    function onNewService(): ?bool
    {
        return true;
    }

    function writeErr(\Exception $e, $which = __CLASS__, $type = 'info'): bool
    {
        $logFileName = $which . '.log.' . date('Ymd', time());

        //给程序员看的
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();

        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content, LOG_PATH, $type, $logFileName);
    }

    //true 说明是XinDongService要用结果，不需要给controller打印输出
    function setCheckRespFlag(bool $flag)
    {
        $this->checkRespFlag = $flag;
        return $this;
    }

    //返回结果给信动controller
    function createReturn($code = 500, $paging = null, $result = [], $msg = null): array
    {
        $data = [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $msg,
            'checkRespFlag' => $this->checkRespFlag,
        ];

        return $data;
    }


}
