<?php

namespace App\HttpController\Business\Provide;

use App\Csp\Service\CspService;
use App\HttpController\Index;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestRecode;
use App\HttpController\Models\Provide\RequestUserApiRelationship;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use Carbon\Carbon;
use EasySwoole\Mysqli\QueryBuilder;
use EasySwoole\RedisPool\Redis;
use Illuminate\Support\Facades\Log;
use wanghanwanghan\someUtils\control;

class ProvideBase extends Index
{
    public $ldListUrl;
    public $qqListUrl;

    public $requestTime;
    public $responseTime;

    public $userId;//用户主键               本类中添加
    public $provideApiId;//对外接口主键      本类中添加
    public $requestRealIp;//真实ip          本类中添加
    public $requestId;//随机生成的请求uuid   本类中添加
    public $requestUrl;//                  本类中添加
    public $requestData;//                 本类中添加
    public $responseCode;//返回值
    public $responsePaging;//返回分页
    public $responseData;//返回数据
    public $responseMsg;//返回消息
    public $responseInfo;//返回信息         本类中添加
    public $spendTime;//请求耗时            本类中添加
    public $spendMoney;//对外接口需付费金额   本类中添加

    public $csp;
    public $cspKey;
    public $cspTimeout = 10;

    //在afterAction里判断的标志，如果之前已经输出过，那么afterAction将不再输出
    //onRequest返回false后，afterAction还会执行
    public $alreadyWriteJson = false;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        $this->csp = CspService::getInstance()->create();
        $this->cspKey = control::getUuid();

        $this->ldListUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');
        $this->qqListUrl = CreateConf::getInstance()->getConf('qianqi.baseUrl');

        $this->requestTime = microtime(true);

        isset($this->request()->getHeader('x-real-ip')[0]) ?
            $this->requestRealIp = $this->request()->getHeader('x-real-ip')[0] :
            $this->requestRealIp = '';

        $this->requestId = control::getUuid();
        $this->requestUrl = $this->request()->getSwooleRequest()->server['path_info'];
        $this->getRequestData();

        //request user check
        return $this->requestUserCheck();
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
        $this->responseTime = microtime(true);
        $this->spendTime = $this->responseTime - $this->requestTime;

        try {
            //行为记录
            RequestRecode::create()->addSuffix(date('Y'))->data([
                'userId' => $this->userId,
                'provideApiId' => $this->provideApiId,
                'requestIp' => $this->requestRealIp,
                'requestId' => $this->requestId,
                'requestUrl' => mb_substr($this->requestUrl, 0, 256),
                'requestData' => jsonEncode($this->requestData, false),
                'responseCode' => $this->responseCode,
                'responseData' => jsonEncode($this->responseData, false),
                'spendTime' => $this->spendTime,
                'spendMoney' => $this->spendMoney,
            ])->save();
            //减金额
            $this->spendMoney <= 0 || RequestUserInfo::create()->where('id', $this->userId)->update([
                'money' => QueryBuilder::dec($this->spendMoney)
            ]);
        } catch (\Throwable $e) {
            $this->writeErr($e, __FUNCTION__);
        }

        //如果是rsa模式，只返回指定内容
        if (isset($this->requestData['encrypt']) && isset($this->requestData['content'])) {
            $this->requestData = [
                'appId' => $this->requestData['appId'],
                'time' => $this->requestData['time'],
                'sign' => $this->requestData['sign'],
                'encrypt' => $this->requestData['encrypt'],
                'content' => $this->requestData['content'],
            ];
        }

        $this->responseInfo = [
            'requestUrl' => $this->requestUrl,
            'requestId' => $this->requestId,
            'requestData' => $this->requestData,
            'spendTime' => $this->spendTime,
        ];

        //如果之前已经输出过，那么afterAction将不再输出
        $this->alreadyWriteJson ?: $this->writeJson(
            $this->responseCode,
            $this->responsePaging,
            $this->responseData,
            $this->responseMsg,
            $this->responseInfo
        );
    }

    //重写writeJson
    function writeJson($statusCode = 200, $paging = null, $result = null, $msg = null, $info = []): bool
    {
        if (!$this->response()->isEndResponse()) {
            if (!empty($paging) && is_array($paging)) {
                foreach ($paging as $key => $val) {
                    $paging[$key] = $val - 0;
                }
            }
            $data = [
                'code' => $statusCode,
                'paging' => $paging,
                'result' => $result,
                'msg' => $msg,
                'info' => $info,
            ];
            $this->response()->write(jsonEncode($data, false));
            $this->response()->withHeader('Content-type', 'application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            $this->alreadyWriteJson = true;
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

        //有可能是rsa + aes的数据
        if (isset($requestData['encrypt']) && isset($requestData['content'])) {
            if (!empty($requestData['appId'])) {
                $info = RequestUserInfo::create()->where('appId', $requestData['appId'])->get();
                //拿私钥文件名
                $rsa_pri_name = $info->getAttr('rsaPri');
                //私钥解密
                $stream = file_get_contents(RSA_KEY_PATH . $rsa_pri_name);
                $aes_key = control::rsaDecrypt($requestData['encrypt'], $stream, 'pri');
                //aes解密
                $content = control::aesDecode($requestData['content'], $aes_key, 128, $requestData['encodeMode']);
                $content_arr = jsonDecode($content);
                if (is_array($content_arr)) {
                    $requestData = array_merge($requestData, $content_arr);
                }
            }
        }

        if (isset($requestData['pageSize']) && !isset($requestData['xindong'])) {
            $requestData['pageSize'] > 10 ?
                $requestData['pageSize'] = 10 :
                $requestData['pageSize'] = $requestData['pageSize'] - 0;
        }

        $this->requestData = $requestData;

        return (isset($requestData[$key])) ? $requestData[$key] : $default;
    }

    function requestUserCheck(): bool
    {
        $appId = $this->requestData['appId'] ?? '';
        $time = $this->requestData['time'] ?? '';
        $sign = $this->requestData['sign'] ?? '';

        if (empty($appId) || empty($time) || empty($sign)) {
            $this->writeJson(600, null, null, '鉴权参数不能是空');
            return false;
        }
        if (!is_string($appId)) {
            $this->writeJson(601, null, null, 'appId格式不正确');
            return false;
        }
        $time = str_pad($time, 13, '0', STR_PAD_RIGHT);
        if (!is_numeric($time) || $time < 0 || strlen($time) !== 13) {
            $this->writeJson(602, null, null, 'time格式不正确');
            return false;
        }
        if (!is_string($sign)) {
            $this->writeJson(603, null, null, 'sign格式不正确');
            return false;
        }
        if (abs((microtime(true) * 1000 - $time) > 300 * 1000)) {
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

        $this->userId = $userInfo->getAttr('id');

        try {
            $apiInfo = RequestApiInfo::create()->where('path', $this->requestUrl)->get();
            if (empty($apiInfo)) {
                $this->writeJson(607, null, null, '请求接口不存在');
                return false;
            }
            $this->provideApiId = $apiInfo->getAttr('id');
            $relationshipCheck = RequestUserApiRelationship::create()
                ->where([
                    'userId' => $this->userId,
                    'apiId' => $this->provideApiId,
                    'status' => 1
                ])->get();
            if (empty($relationshipCheck)) {
                $this->writeJson(608, null, null, '没有接口请求权限');
                return false;
            }
            $this->spendMoney = $relationshipCheck->getAttr('price');
            if ($userInfo->getAttr('money') < $this->spendMoney) {
                $this->writeJson(609, null, null, '余额不足');
                return false;
            }
        } catch (\Throwable $e) {
            $this->writeJson(605, null, null, '服务器繁忙');
            $this->writeErr($e, __FUNCTION__);
            return false;
        }

        $appSecret = $userInfo->getAttr('appSecret');
        $createSign10 = substr(strtoupper(md5($appId . $appSecret . substr($time, 0, 10))), 0, 30);
        $createSign13 = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

        //10位时间和13位时间
        if (!in_array($sign, [$createSign10, $createSign13], true)) {
            $this->writeJson(610, null, null, '签名验证错误');
            return false;
        }

        //sign重复提交检查
        $sign_check = Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($sign) {
            $redis->select(14);
            $check = !!$redis->sAdd('sign_check_' . Carbon::now()->format('YmdH'), $sign);
            $redis->expire('sign_check_' . Carbon::now()->format('YmdH'), control::randNum(2) * 6);
            return $check;
        });

        if ($sign_check === false) {
            $this->writeJson(611, null, null, 'sign重复提交');
            return false;
        }

        if ($userInfo->getAttr('status') !== 1) {
            $this->writeJson(612, null, null, 'appId不可用');
            return false;
        }

        $allowIp = $userInfo->getAttr('allowIp');

        if (!empty($allowIp)) {
            if (array_search($this->requestRealIp, explode(',', $allowIp), true)) {
                $this->writeJson(613, null, null, '请求ip不在白名单');
                return false;
            }
        }

        return true;
    }


}