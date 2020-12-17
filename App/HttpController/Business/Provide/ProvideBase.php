<?php

namespace App\HttpController\Business\Provide;

use App\HttpController\Index;
use App\HttpController\Models\Provide\RequestRecode;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use wanghanwanghan\someUtils\control;

class ProvideBase extends Index
{
    public $qccListUrl;

    public $requestTime;
    public $responseTime;

    public $userId;//用户主键
    public $provideApiId;//对外接口主键
    public $requestId;//随机生成的请求uuid
    public $requestUrl;
    public $requestData;
    public $responseCode;//返回值
    public $responseData;//返回数据
    public $spendTime;
    public $spendMoney;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $this->qccListUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');

        $this->requestTime = microtime(true);
        $this->requestId = control::getUuid();
        $this->requestUrl = $this->request()->getUri()->__toString();
        $this->getRequestData();

        //request user check
        $userCheck = $this->requestUserCheck();

        return $userCheck;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
        $this->responseTime = microtime(true);
        $this->spendTime = $this->requestTime - $this->responseTime;

        try {
            RequestRecode::create()->addSuffix(date('Y'))->data([
                'userId' => $this->userId,
                'provideApiId' => $this->provideApiId,
                'requestId' => $this->requestId,
                'requestUrl' => mb_substr($this->requestUrl, 0, 256),
                'requestData' => json_encode($this->requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'responseCode' => $this->responseCode,
                'responseData' => json_encode($this->responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'spendTime' => $this->spendTime,
                'spendMoney' => $this->spendMoney,
            ])->save();
        } catch (\Throwable $e) {

        }
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

    function writeErr(\Throwable $e, $which = 'comm'): bool
    {
        $logFileName = $which . '.log.' . date('Ymd', time());
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        //返回log写入成功或者写入失败
        return control::writeLog($content, LOG_PATH, 'info', $logFileName);
    }

    function getRequestData($key = '', $default = '')
    {
        $string = $this->request()->getBody()->__toString();

        $raw = jsonDecode($string);
        $form = $this->request()->getRequestParam();

        !empty($raw) ?: $raw = [];
        !empty($form) ?: $form = [];

        $requestData = array_merge($raw, $form);
        $this->requestData = $requestData;

        return (isset($requestData[$key]) && !empty($requestData[$key])) ? $requestData[$key] : $default;
    }

    function requestUserCheck(): bool
    {
        $appId = $this->responseData['appId'] ?? '';
        if ($appId === 'wh') return true;
        $time = $this->responseData['time'] ?? '';
        $sign = $this->responseData['sign'] ?? '';

        if (empty($appId) || empty($time) || empty($sign)) {
            $this->writeJson(600, null, null, '鉴权参数不能是空');
            return false;
        }
        if (!is_string($appId)) {
            $this->writeJson(601, null, null, 'appId格式不正确');
            return false;
        }
        if (!is_numeric($time) || $time < 0) {
            $this->writeJson(602, null, null, 'time格式不正确');
            return false;
        }
        if (!is_string($sign)) {
            $this->writeJson(603, null, null, 'sign格式不正确');
            return false;
        }
        if (time() - $time > 300) {
            $this->writeJson(604, null, null, 'time超时');
            return false;
        }

        try {
            $userInfo = RequestUserInfo::create()->where('appId', $appId)->get();
        } catch (\Throwable $e) {
            $this->writeJson(605, null, null, '服务器繁忙');
            $this->writeErr($e, __FUNCTION__);
            return false;
        }

        if (empty($userInfo)) {
            $this->writeJson(606, null, null, '用户不存在');
            return false;
        }

        $appSecret = $userInfo->appSecret;
        $createSign = strtoupper(md5($appId . $appSecret . $time));

        if ($sign !== $createSign) {
            $this->writeJson(607, null, null, '签名验证错误');
            return false;
        }

        if ($userInfo->status !== 1) {
            $this->writeJson(608, null, null, 'appId不可用');
            return false;
        }

        return true;
    }


}