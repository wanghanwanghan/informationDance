<?php

namespace App\HttpController\Business;

use App\HttpController\Index;
use App\HttpController\Service\RequestUtils\LimitService;
use wanghanwanghan\someUtils\control;

class BusinessBase extends Index
{
    //继承这个主要是为了可以writeJson

    //也是为了onRequest
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $checkRouter = $this->checkRouter();

        $checkToken = $this->checkToken();

        $checkLimit = $this->checkLimit();

        return ($checkRouter || ($checkToken && $checkLimit));
    }

    //还有afterAction
    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //重写writeJson
    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null)
    {
        if (!$this->response()->isEndResponse()) {
            $data = [
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $result,
                'msg' => $msg
            ];

            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);

            return true;

        } else {
            return false;
        }
    }

    //链接池系列抛出异常
    function writeErr(\Throwable $e, $which = 'comm'): bool
    {
        //给用户看的
        $this->writeJson(9527, null, null, $which . '错误');

        $logFileName = $which . '.log.' . date('Ymd', time());

        //给程序员看的
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();

        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";

        //返回log写入成功或者写入失败
        return control::writeLog($content, LOG_PATH, 'info', $logFileName);
    }

    //check router
    private function checkRouter(): bool
    {
        //直接放行的url，只判断url最后两个在不在数组中
        $pass = \Yaconf::get('env.passRouter');

        // /api/v1/comm/create/verifyCode
        $path = $this->request()->getSwooleRequest()->server['path_info'];

        $path = rtrim($path, '/');
        $path = explode('/', $path);

        if (!empty($path)) {
            //检查url在不在直接放行数组
            $len = count($path);

            //取最后两个
            $path = implode('/', [$path[$len - 2], $path[$len - 1]]);

            //在数组里就放行
            if (in_array($path, $pass)) return true;
        }

        return false;
    }

    //check token
    private function checkToken(): bool
    {
        $requestToken = $this->userToken;

        $checkToken = true;

        return $checkToken;
    }

    //check limit
    private function checkLimit(): bool
    {
        return LimitService::getInstance()->check($this->userToken);
    }
}
