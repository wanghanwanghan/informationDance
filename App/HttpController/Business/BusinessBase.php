<?php

namespace App\HttpController\Business;

use App\HttpController\Index;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\RequestUtils\LimitService;
use App\HttpController\Service\RequestUtils\StatisticsService;
use App\HttpController\Service\User\UserService;
use wanghanwanghan\someUtils\control;

class BusinessBase extends Index
{
    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $checkRouter = $this->checkRouter();

        $checkToken = $this->checkToken();

        if (!$checkRouter && !$checkToken) $this->writeJson(201, null, null, 'token错误');

        $checkLimit = $this->checkLimit();

        if (!$checkLimit) $this->writeJson(201, null, null, '到达limit上限');

        //统计
        (new StatisticsService($this->request()))->byPath();

        return ($checkRouter || ($checkToken && $checkLimit));
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    //重写writeJson
    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null)
    {
        if (!$this->response()->isEndResponse()) {

            if (!empty($paging) && is_array($paging)) {
                foreach ($paging as $key => $val) {
                    $paging[$key] = (int)$val;
                }
            }

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
        $pass = CreateConf::getInstance()->getConf('env.passRouter');

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
        $requestToken = $this->request()->getHeaderLine('authorization');

        if (empty($requestToken) || strlen($requestToken) < 50) return false;

        try {
            $res = User::create()->where('token', $requestToken)->get();
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
            return false;
        }

        if (empty($res)) return false;

        $tokenInfo = UserService::getInstance()->decodeAccessToken($requestToken);

        if (!is_array($tokenInfo) || count($tokenInfo) != 3) return false;

        $reqPhone = $this->request()->getRequestParam('phone') ?? '';

        $tokenPhone = current($tokenInfo);

        if (strlen($tokenPhone) != 11 || strlen($reqPhone) != 11) return false;

        return (int)$reqPhone === (int)$tokenPhone ? true : false;
    }

    //check limit
    private function checkLimit(): bool
    {
        $token = $this->request()->getHeaderLine('authorization');

        isset($this->request()->getHeader('x-real-ip')[0]) ? $realIp = $this->request()->getHeader('x-real-ip')[0] : $realIp = null;

        return LimitService::getInstance()->check($token, $realIp);
    }

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }
}
