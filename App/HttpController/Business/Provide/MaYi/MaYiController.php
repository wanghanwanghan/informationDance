<?php

namespace App\HttpController\Business\Provide\MaYi;

use App\HttpController\Index;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
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

    function writeJsons($result): bool
    {
        $timestamp = microtime(true) * 1000;
        if (strlen($timestamp) !== 13) {
            $timestamp = substr($timestamp, 0, strpos($timestamp, '.'));
        }
        $timestamp .= '';

        if (!$this->response()->isEndResponse()) {
            $data = [
                'code' => $result['code'],
                'data' => $result['result'],
                'msg' => $result['msg'],
                'timestamp' => $timestamp - 0,
            ];
            $this->response()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus(200);
            return true;
        } else {
            return false;
        }
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

        $res = (new MaYiService())->authEnt($data);

        $res['result']['nsrsbh'] = $data['socialCredit'];
        $res['result']['authId'] = $data['requestId'];
        $res['result']['authTime'] = Carbon::now()->format('Y-m-d H:i:s');

        switch ($res['code']) {
            case 600:
            case 605:
                $res['code'] = '0001';
                $res['result']['authResultCode'] = '0';
                $res['result']['authResultMsg'] = '认证授权失败';
                break;
            default:
                $res['code'] = '0000';
                $res['result']['authResultCode'] = '1';
                $res['result']['authResultMsg'] = '认证授权通过';
        }

        return $this->writeJsons($res);
    }

}