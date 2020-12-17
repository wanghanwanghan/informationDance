<?php

namespace App\HttpController\Business\Provide;

use App\HttpController\Index;
use App\HttpController\Models\Provide\RequestRecode;
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

        //user check
        $userCheck = $this->userCheck();

        return $userCheck;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
        $this->responseTime = microtime(true);
        $this->spendTime = $this->requestTime - $this->responseTime;

        try
        {
            RequestRecode::create()->addSuffix(date('Y'))->data([
                'userId' => $this->userId,
                'provideApiId' => $this->provideApiId,
                'requestId' => $this->requestId,
                'requestUrl' => mb_substr($this->requestUrl,0,256),
                'requestData' => json_encode($this->requestData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'responseCode' => $this->responseCode,
                'responseData' => json_encode($this->responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'spendTime' => $this->spendTime,
                'spendMoney' => $this->spendMoney,
            ])->save();
        }catch (\Throwable $e)
        {

        }
    }

    function getRequestData($key = '', $default = '')
    {
        $string = $this->request()->getBody()->__toString();

        $raw = jsonDecode($string);
        $form = $this->request()->getRequestParam();

        !empty($raw) ?: $raw = [];
        !empty($form) ?: $form = [];

        $requestData = array_merge($raw,$form);
        $this->requestData = $requestData;

        return (isset($requestData[$key]) && !empty($requestData[$key])) ? $requestData[$key] : $default;
    }

    function userCheck(): bool
    {








        return true;
    }
}