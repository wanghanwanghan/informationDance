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

    function writeErr($e, $which = __FUNCTION__, $type = 'info'): bool
    {
        $logFileName = $which . '.log.' . date('Ymd', time());

        //给程序员看的
        if ($e instanceof \Throwable || $e instanceof \Exception) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        } else {
            $content = '$e类别不明';
        }

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

    //
    function useThisKey($arr, $salt = '')
    {
        ksort($arr);

        empty($salt) ?: $arr[] = $salt;

        $arr = implode(',', $arr);

        return md5($arr);
    }

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }
}
