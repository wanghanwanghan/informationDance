<?php

date_default_timezone_set('Asia/Shanghai');

include '../../vendor/autoload.php';
include '../../bootstrap.php';
include 'classs/Sm4.php';

// 毫秒时间戳
function getMicroTime(): string
{
    return substr(microtime(true) * 1000, 0, 13);
}

function jsonEn($arr)
{
    return json_encode(json_decode(json_encode($arr), true), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}

function jsonDe($arr)
{
    return json_decode($arr, true);
}

function sha256WithRSA($str, $private_key): string
{
    $pkeyid = openssl_pkey_get_private($private_key);
    openssl_sign($str, $signature, $pkeyid, OPENSSL_ALGO_SHA256);
    return bin2hex($signature);
}

// RSA加密后 输出16进制
function rsaEncrypt(string $str = '', string $key = ''): string
{
    $publicKey = openssl_get_publickey($key);
    openssl_public_encrypt($str, $encrypted, $publicKey);
    return bin2hex($encrypted);
}

// sm4加密后 输出16进制
function sm4Encrypt($str, $secretKey): string
{
    $obj = new Sm4();
    return $obj->setKey($secretKey)->encryptData($str);
}

// 统一curl
function send($array, $url)
{
    // 准备发送给苏宁的数据
    $sendData = [];

    // array 里有 payloadOri 和 publicDataOri 两个原始数组

    // ======================= sendData 中 secretKey =======================
    // 随机密钥 原始明文
    $secretKeyOri = md5(getMicroTime());

    // 服务端公钥
    $serverPub = file_get_contents(getcwd() . '/file/server_pub.pem');

    // RSA加密后 16进制
    $secretKeyRSA = rsaEncrypt($secretKeyOri, $serverPub);

    // ======================= sendData 中 payload =======================

    // 先去掉空值
    $payload = array_filter($array['payloadOri'], function ($raw) {
        return !(($raw === '' || $raw === null));
    });

    // sm4加密
    $payloadSM4 = sm4Encrypt(jsonEn($payload), $secretKeyOri);

    // 业务数据 json格式字符串
    $sendData['payload'] = $payloadSM4;

    // ======================= sendData 中 signature =======================

    // 去掉公共字段里的空值
    $publicData = array_filter($array['publicDataOri'], function ($raw) {
        return !(($raw === '' || $raw === null));
    });

    // 补全其他 公共参数
    $publicData['appCode'] = '91110108MA01KPGK0L0002';
    $publicData['timestamp'] = date('Y-m-d H:i:s');
    $publicData['algorithm'] = 'SHA256withRSA';
    $publicData['terminal'] = '1';
    $publicData['ipAddress'] = '39.105.35.154';

    $publicData['secretKey'] = $secretKeyRSA;

    // 签名 对全部body字段签名
    $params = array_merge($publicData, $payload);

    unset($params['file_0']);

    // 合并顺序 转换格式 urlencode 类型 不参与签名
    foreach ($params as $k => $v) {
        if (is_array($v)) {
            ksort($v);
            $params[$k] = http_build_query($v);
        }
    }

    ksort($params);

    // 得到签名原始字符串 明文
    $signatureOri = http_build_query($params);

    // 客户端私钥
    $cli_pri = file_get_contents(getcwd() . '/file/cli_pri.pem');

    // 签名私钥加密 输出16进制
    $signature = sha256WithRSA($signatureOri, $cli_pri);

    // 签名
    $sendData['signature'] = $signature;

    // ======================= 准备发送 =======================
    $sendData = array_merge($sendData, $publicData);

    // 发送
    echo '发送:' . jsonEn($sendData) . PHP_EOL;

    $header = [
        'charset: UTF-8',
        'content-type: multipart/form-data',
        'version: 1.0',
        "appCode: 91110108MA01KPGK0L0002",
    ];

    $url = $url . "91110108MA01KPGK0L0002/{$array['publicDataOri']['transCode']}";

    echo 'url:' . $url . PHP_EOL;

    $curl = curl_init(); //初始化
    curl_setopt($curl, CURLOPT_URL, $url); //设置请求地址
    curl_setopt($curl, CURLOPT_HTTPHEADER, $header); //设置请求header
    curl_setopt($curl, CURLOPT_POST, true); //设置post方式请求
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); //几秒后没链接上就自动断开
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);// 对认证证书来源的检查
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);// 从证书中检查SSL加密算法是否存在
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); //返回值不直接显示
    curl_setopt($curl, CURLOPT_POSTFIELDS, $sendData); //提交的数据
    curl_setopt($curl, CURLOPT_HEADER, false); //不输出响应头
    curl_setopt($curl, CURLOPT_SSLCERTTYPE, 'PEM'); // PHP的 CURL 只支持 PEM 方式
    curl_setopt($curl, CURLOPT_SSLCERT, getcwd() . '/file/cert.pem'); // cert.pem文件路径
    curl_setopt($curl, CURLOPT_SSLCERTPASSWD, 'wanghan123'); // 证书密码
    curl_setopt($curl, CURLOPT_SSLKEYTYPE, 'PEM'); // PHP的 CURL 只支持 PEM 方式
    curl_setopt($curl, CURLOPT_SSLKEY, getcwd() . '/file/private.pem'); // private.pem 文件路径
    curl_setopt($curl, CURLOPT_VERBOSE, false); // 输出详细调试信息
    $res = curl_exec($curl); //发送请求
    $error = curl_error($curl);
    curl_close($curl);
    dd($res, $error);
}

// 统一文件流上传接口
function fileStreamUpload()
{
    // sit环境url
    $url = 'https://fsoftssit.suningbank.com:2443/fsoftssit1/';


}

// 企业绑卡开户 上传影像文件
function uploadEntImg()
{
    // 营业执照文件
    $busiLicenceStream = file_get_contents('./file/yingyezhizhao.png');
    // 法人身份证正面
    $legalPersonInfo = file_get_contents('./file/zheng.png');
    // 法人身份证国徽
    $legalPerson = file_get_contents('./file/fan.png');
    // 场景码
    $sceneCode = '1102';
    // 交易码
    $transCode = 'snb.fsofts.fileStream.upload';
    // 扩展字段
    $extendParam = [
        'legalPersonIdNo' => '',// 法人身份证号
        'legalPersonName' => '',// 法人姓名
    ];


}

// 企业绑卡开户
function createEntAccount()
{
    // 先传几个证件图片给苏宁

    // 营业执照副本url
    $file = file_get_contents(getcwd() . '/img/每日信动企业一.jpg');

    $array = [
        'payloadOri' => [
            'merchantId' => 'MRXD0002',// 商户编号 此函数固定的
            'sceneCode' => '1102',// 场景码 此函数固定的
            'fileDigestMap' => [
                'file_0' => md5($file),// 文件摘要 MD5
                'file_0_type' => 'Z201',// 营业执照
            ],
            'extendParam' => [
                'legalPersonIdNo' => '130625198801010336',
                'legalPersonName' => '每日信动法人一',
            ],
        ],
        'publicDataOri' => [
            'file_0' => new \CURLFile(realpath('./img/每日信动企业一.jpg'), 'image/jpeg', 'test_yyzz_1.jpg'),
            'channelId' => 'MRXD0001',// 渠道号
            'channelSerialNo' => 'mrxd' . getMicroTime(),// 流水号
            'transCode' => 'snb.fsofts.fileStream.upload',// 交易码 此函数固定的
        ],
    ];

    send($array, 'https://fsoftssit.suningbank.com:2443/fsoftssit1/');

    dd('兜底');

    $busiLicenceUrl = '';

    // 法人国徽页url 经办人国徽页url
    $legalPersonIdUrlBack = '';
    $operatorIdUrlBack = '';

    // 法人个人信息页url 经办人个人信息页url
    $legalPersonIdUrlFront = '';
    $operatorIdUrlFront = '';


}


createEntAccount();


// ==================================================== 公共参数 ====================================================

// 应用编号 固定的
$appCode = '91110108MA01KPGK0L0002';
// 商户编号 固定的
$merchantId = 'MRXD0002';
// 时间戳 yyyy-MM-dd HH:mm:ss
$timestamp = date('Y-m-d H:i:s', time());
// 渠道号 固定的
$channelId = 'MRXD0001';
// ❌设备号 终端设备唯一编号，安卓设备建议使用IMEI，如IMEI无值可传递MEID或Android ID，IOS设备可传UUID
$deviceId = '';
// 签名算法 默认SHA256withRSA
$algorithm = 'SHA256withRSA';
// 终端类型 1:PC 2:ANDROID 3:IOS 4:H5 5:API
$terminal = 'API';
// ❌GPS解析省市 例如：江苏/南京
$gps = '';
// IP地址 127.0.0.1
$ipAddress = '39.105.35.154';
// ❌物理地址 例如：1c:78:39:08:96:16
$macAddress = '';
// ❌操作系统版本 例如：IOS 12.1，ANDROID 8.1
$osVersion = '';
// ❌用户唯一标识 行方生成的用户编号
$openId = '';
