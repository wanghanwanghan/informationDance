<?php

namespace App\HttpController\Business\Provide\MaYi;

use App\HttpController\Index;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\MaYi\MaYiService;
use wanghanwanghan\someUtils\control;

class MaYiController extends Index
{
    public $appId;

    function onRequest(?string $action): ?bool
    {
        $this->appId = CreateConf::getInstance()->getConf('mayi.appId');
        return parent::onRequest($action);
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

        $requestData = array_merge($raw, $form);

        if (empty($key)) {
            return $requestData;
        }

        return (isset($requestData[$key])) ? $requestData[$key] : $default;
    }

    //蚂蚁发过来的企业五要素
    function invEntList(): bool
    {
        CommonService::getInstance()->log4PHP($this->getRequestData());

        $tmp['head'] = $this->getRequestData('head');
        $tmp['body'] = $this->getRequestData('body');

        $data['entName'] = $tmp['body']['companyName'];
        $data['socialCredit'] = $tmp['body']['nsrsbh'];
        $data['legalPerson'] = $tmp['body']['legalName'];
        $data['idCard'] = $tmp['body']['idCode'];
        $data['phone'] = $tmp['body']['mobile'];
        $data['requestId'] = control::getUuid();

        //$res = (new MaYiService())->authEnt($data);

        return $this->writeJson(200, $data);
    }

}