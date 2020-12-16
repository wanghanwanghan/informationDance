<?php

namespace App\HttpController\Business\Provide;

use App\HttpController\Index;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use wanghanwanghan\someUtils\control;

class ProvideBase extends Index
{
    public $qccListUrl;
    public $requestData;
    public $requestTime;
    public $requestId;
    public $requestUrl;
    public $responseTime;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);
        $this->requestTime = time();
        $this->requestId = control::getUuid();
        $this->requestUrl = $this->request()->getUri()->__toString();
        $this->qccListUrl = CreateConf::getInstance()->getConf('qichacha.baseUrl');
        $this->getRequestData();
        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
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
}