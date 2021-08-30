<?php

namespace App\HttpController\Business\Provide\MaYi;

use App\HttpController\Index;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use wanghanwanghan\someUtils\control;

class MaYiController extends Index
{
    public $appId;
    public $appSecret;

    function onRequest(?string $action): ?bool
    {
        $this->appId = CreateConf::getInstance()->getConf('mayi.appId');
        $this->appSecret = CreateConf::getInstance()->getConf('mayi.appSecret');
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
                'msg' => $result['msg'] ?? '处理成功',
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
        $tmp['head'] = $this->getRequestData('head');
        $tmp['body'] = $this->getRequestData('body');

        if (!isset($tmp['head']['sign'])) {
            return $this->writeJsons([
                'code' => '0001',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '认证授权失败',
                ],
            ]);
        }

        //拿rsa公钥
        $userInfo = RequestUserInfo::create()->where('appId', $this->appId)->get();
        $rsaPub = RSA_KEY_PATH . $userInfo->getAttr('rsaPub');
        if (!file_exists($rsaPub)) {
            CommonService::getInstance()->log4PHP("{$this->appId} 的Rsa不存在");
            return $this->writeJsons([
                'code' => '0001',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '认证授权失败',
                ],
            ]);
        }

        $key = control::rsaDecrypt($tmp['head']['sign'], file_get_contents($rsaPub), 'pub');

        $key = explode('_', $key);

        if (!is_array($key) || $key[0] !== $this->appId) {
            CommonService::getInstance()->log4PHP("{$this->appId} 的解密失败");
            return $this->writeJsons([
                'code' => '0001',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '认证授权失败',
                ],
            ]);
        }

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

    //企业认证授权结果查询接口
    function invSelectAuth(): bool
    {
        $tmp['head'] = $this->getRequestData('head');
        $tmp['body'] = $this->getRequestData('body');

        $data['nsrsbh'] = $tmp['body']['nsrsbh'];
        $data['authType'] = $tmp['body']['authType']; // PRELOAN 贷前 POSTLOAN 贷后

        $info = AntAuthList::create()->where([
            'socialCredit' => $data['nsrsbh']
        ])->get();

        $res['code'] = '0000';
        $res['result'] = [
            'nsrsbh' => $data['nsrsbh'],
            'authId' => $info->getAttr('requestId'),
            'authTime' => date('Y-m-d H:i:s', $info->getAttr('requestDate')),
            'authResultCode' => '1',
            'authResultMsg' => '认证授权通过',
        ];

        return $this->writeJsons($res);
    }

}