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
    function onRequest(?string $action): ?bool
    {
        $this->dev_appId = CreateConf::getInstance()->getConf('mayi.dev_appId');
        $this->dev_appSecret = CreateConf::getInstance()->getConf('mayi.dev_appSecret');
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

        if (empty($tmp['head']['appId'])) {
            return $this->writeJsons([
                'code' => '0001',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '缺少appId',
                ],
            ]);
        }

        if (empty($tmp['head']['sign'])) {
            return $this->writeJsons([
                'code' => '0001',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '缺少sign',
                ],
            ]);
        }

        //拿rsa公钥
        $userInfo = RequestUserInfo::create()->where('appId', $tmp['head']['appId'])->get();

        if (empty($userInfo)) {
            return $this->writeJsons([
                'code' => '9999',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '系统异常',
                ],
            ]);
        }

        $rsaPub = RSA_KEY_PATH . $userInfo->getAttr('rsaPub');

        if (!file_exists($rsaPub)) {
            return $this->writeJsons([
                'code' => '9999',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '系统异常',
                ],
            ]);
        }

        $pkeyid = openssl_get_publickey(file_get_contents($rsaPub));

        //三个协议文件必传，要不都需要盖章，要不都不用盖章，不传说明是老的授权方式

        //老方式
        $v = [
            'legalName' => $tmp['body']['legalName'] ?? '',
            'nsrsbh' => $tmp['body']['nsrsbh'] ?? '',
            'idCard' => $tmp['body']['idCard'] ?? '',
            'companyName' => $tmp['body']['companyName'] ?? '',
            'mobile' => $tmp['body']['mobile'] ?? '',
        ];

        //新方式
        if (!empty($tmp['body']['fileData'])) {
            $v['orderNo'] = $tmp['body']['orderNo'] ?? '';

            foreach ($tmp['body']['fileData'] as &$one) {
                ksort($one);
            }
            unset($one);

            $v['fileData'] = $tmp['body']['fileData'];
            ksort($v);
        }

        CommonService::getInstance()->log4PHP($v);

        $ret = openssl_verify(
            jsonEncode($v, false),
            base64_decode($tmp['head']['sign']),
            $pkeyid,
            OPENSSL_ALGO_MD5
        );

        if ($ret !== 1) {
            return $this->writeJsons([
                'code' => '0002',
                'result' => [
                    'authResultCode' => '0',
                    'authResultMsg' => '验签失败',
                ],
            ]);
        }

        $data['entName'] = $tmp['body']['companyName'] ?? '';
        $data['socialCredit'] = $tmp['body']['nsrsbh'] ?? '';
        $data['legalPerson'] = $tmp['body']['legalName'] ?? '';
        $data['idCard'] = $tmp['body']['idCard'] ?? '';
        $data['phone'] = $tmp['body']['mobile'] ?? '';
        $data['requestId'] = control::getUuid();
        $data['belong'] = $userInfo->getAttr('id');
        $data['fileData'] = $tmp['body']['fileData'] ?? '';
        $data['orderNo'] = $tmp['body']['orderNo'] ?? '';

        $res = (new MaYiService())->authEnt($data);

        $res['result']['nsrsbh'] = $data['socialCredit'];
        $res['result']['authId'] = $data['requestId'];
        $res['result']['authTime'] = Carbon::now()->format('Y-m-d H:i:s');

        switch ($res['code']) {
            case 600:
            case 605:
                $res['code'] = '0001';
                $res['result']['authResultCode'] = '0';
                $res['result']['authResultMsg'] = '缺少参数';
                break;
            case 606:
            case 615:
                $res['code'] = '9999';
                $res['result']['authResultCode'] = '0';
                $res['result']['authResultMsg'] = '系统异常';
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