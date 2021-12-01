<?php

namespace App\HttpController\Service;

use App\HttpController\Models\Provide\RequestSourceRecode;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use wanghanwanghan\someUtils\control;

class ServiceBase
{
    //各个service在返回结果之前进行返回值检测
    public $checkRespFlag = false;

    //信动调用数据源接口记次用
    public $requestId = null;

    function __construct()
    {
        $this->requestId = control::getUuid();
        return true;
    }

    function onNewService(): ?bool
    {
        return true;
    }

    function writeErr($e, $which = __FUNCTION__, $type = 'info'): bool
    {
        $logFileName = $which . '.log.' . date('Ymd', time());

        //给程序员看的
        if ($e instanceof \Throwable) {
            $file = $e->getFile();
            $line = $e->getLine();
            $msg = $e->getMessage();
            $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        } else {
            $content = '$e类别不明';
        }

        //返回log写入成功或者写入失败
        return control::writeLog($content, LOG_PATH, $type, $logFileName);
    }

    //true 说明是XinDongService要用结果，不需要给controller打印输出
    function setCheckRespFlag(bool $flag): ServiceBase
    {
        $this->checkRespFlag = $flag;
        return $this;
    }

    //返回结果给信动controller
    function createReturn($code = 500, $paging = null, $result = [], $msg = null): array
    {
        return [
            'code' => $code,
            'paging' => $paging,
            'result' => $result,
            'msg' => $msg,
            'checkRespFlag' => $this->checkRespFlag,
        ];
    }

    //
    function useThisKey($arr, $salt = ''): string
    {
        ksort($arr);

        empty($salt) ?: $arr[] = $salt;

        $arr = implode(',', $arr);

        return md5($arr);
    }

    //计算分页
    function exprOffset($page, $pageSize): int
    {
        return ($page - 1) * $pageSize;
    }

    //coHttp之前，把传输参数加密
    function beforeSendEncrypt($data, $appId, $merge = []): ?array
    {
        //拿rsa公钥
        $userInfo = RequestUserInfo::create()->where('appId', $appId)->get();
        $rsaPub = RSA_KEY_PATH . $userInfo->getAttr('rsaPub');
        if (!file_exists($rsaPub)) {
            CommonService::getInstance()->log4PHP("{$appId} 的Rsa不存在");
            return null;
        }

        //生成Aes密钥
        $aesKey = control::getUuid();

        //加密Aes密钥
        $encrypt = control::rsaEncrypt($aesKey, file_get_contents($rsaPub));

        //加密内容
        $content = control::aesEncode(jsonEncode($data, false), $aesKey, 256);

        $postData = [
            'encrypt' => $encrypt,
            'content' => $content,
        ];

        if (!empty($merge)) {
            $postData = array_merge($postData, $merge);
        }

        return $postData;
    }

    //
    function afterSendEncrypt($data, $appId): ?array
    {
        $encrypt = $data['encrypt'];
        $content = $data['content'];

        //拿rsa公钥
        $userInfo = RequestUserInfo::create()->where('appId', $appId)->get();
        $rsaPub = RSA_KEY_PATH . $userInfo->getAttr('rsaPub');
        if (!file_exists($rsaPub)) {
            CommonService::getInstance()->log4PHP("{$appId} 的Rsa不存在");
            return null;
        }

        //解密出Aes密钥
        $aesKey = control::rsaDecrypt($encrypt, file_get_contents($rsaPub), 'pub');

        //解密出内容
        $content = openssl_decrypt(base64_decode($content), 'AES-256-ECB', $aesKey, OPENSSL_RAW_DATA);

        return jsonDecode($content);
    }

    //信动调用数据源接口记次
    function recodeSourceCurl(array $ext): void
    {
        foreach ($ext as $key => $val) {
            if (empty($val)) {
                $ext[$key] = null;
            } elseif (!is_string($val)) {
                $ext[$key] = jsonEncode($val, false);
            } else {
                $ext[$key] = trim($val);
            }
        }

        try {
            $info = RequestSourceRecode::create()
                ->addSuffix(date('Y'))
                ->where('requestId', $this->requestId)
                ->get();
            if (empty($info)) {
                $ext['requestId'] = $this->requestId;
                RequestSourceRecode::create()
                    ->addSuffix(date('Y'))
                    ->data($ext)
                    ->save();
            } else {
                $info->update($ext);
            }
        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP($e->getTraceAsString());
        }
    }

}
