<?php

namespace App\HttpController\Service\GuangZhouYinLian;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class GuangZhouYinLianService extends ServiceBase
{
    private $privateKey      = <<<Eof
-----BEGIN RSA PRIVATE KEY-----
MIIEpAIBAAKCAQEA767Gc8oWD9ckvkt6rHRg+AC8yESbAgwfLc+lWh4Izs/rvxqA
db8/hAcpO1h+6tBVzNc+3nitxN53etJyRs2Bjf0nlh74AguaNk1S/kkdzOsGdLDr
5stC/5YOXjhNB2FjzZi5r/0tk5Y/vmsRZIBCTbbwqTvc6WhPNZzDZYUhFyr3oZsR
bf3tRqfWUdTMQdX+TTOjJheVMSzK4375m76qPa36hMaM0ha/cbFdMtWj3WxkaCPm
blfwTdkxOlA7EegL6dW3UH5bRXKWUPGMev0J+LB2fe2e4gp1HoGDJQkhQac04alY
PUfxgNf3pzEJgXOW/qRgKCffK7vgmLiXBGAASQIDAQABAoIBAQDS5KTngxwwaeyB
qCZTkb802GlDieIeRg41H+ztQ1oapyZWq3n2oQXBJQ/pkO9zq0aji41c8TBs9haJ
MpysoexpxCNN2wf7vLu/JgBtkGYxRWcTzAx1scnM2/reeomEgfPwFn7kVFAC+YQz
B0Bxfs1YViyhq/OwSEDR+pKPRDmeiFL4zrPa27JO9TX6lLnwAEB5zgsk/DbpIon9
PO7BejPQ0Dth2K12YmDUGoSNFhgshLGSoYPF+BPAVAYpPOnwmbEq35X85000xi56
XZbU9NoiFABtp8Ft9YCxX+9yBjnraCH5c2SDkT1T6/wBLA8JvTRQtE2SYAPXjs3h
4xug4+CBAoGBAP35J2Gqg49iZum6KH4lOIQLzvjVl0fXy9ZciRI4y8VUvTZHurUT
Tr/ka6HbGfA6ujUO+XOm16Gu5eildbF7FOgM+SvRYspRvzfm8hLZjfhjD/XzZ4qH
As8KApioavelExcX9QLpLAcs5OOlSQFT5wP5Syeg+lYNdo2qfbgI4IyxAoGBAPGY
bU8A7RuPIk9knKvsNOr6FI0tpmgBosBh8MIyD9sScvF3kjEmltkruSCoJE6T9SvS
a3G/SNMFUx6vczIM05pSdXLTDBcBOdRL3yRPpcaDkjj/J9/UV5h7jM2l/YkA3p8n
pm3dkFy31wDlHcnCp8P+IbadJWJEJk284Zm/2zMZAoGBAJzwxAc0SUvncNTptnAN
LBlc+q8FvhAlJ871K7bY5gKw1KOgO539qmImEuTX8fVjNQHomPmAlitRWr0i0dG0
zzx+F9Od9kAzt8ghrGE9kt90x74ihU8zEudBtk0DdeZGWb+hjEQaNpzQfzi1QKHT
aSQpfumkLk3Sz/nG6x04TxphAoGAPrwwFkXNTEy8whUEQfiSPTo3P/nMrlFOa9qC
5EqPp3mA84bzJWQ546bg6cP/uY/eKET5tY3QYUuOq/cvWJ3QDNDAwtJe31JoK+KP
zSQJjiT4QWiweATxwhzDEVu7HGpnZLitFPZl2E28vPTB6XRskA5bvsnLvVqo/6K+
imgxiXECgYBrnmW0i8IRYD9AlQdxJDreBfvrsiW4RdbH5s3Ar/5nmNQpz9TLusgj
FeUxasdoXjSBuMb3Zc/aJQFfZa5Ql64QbMtM1q9v83G1iysPXP+bXqg3Wr/Ea9Jk
ezbvJLTt45TL9P3xd3x2cYNDZfWxga68E73QFjU8T5KdJiw0GNUNnA==
-----END RSA PRIVATE KEY-----
Eof;
    private $app_id          = '5dc387b32c07871d371334e9c45120ba';
    private $timestamp;
    private $v               = '1.0.1';
    private $sign_alg        = 1;
    private $busiMerno       = '898441650210002';
    private $testUrl         = 'https://testapi.gnete.com:9083/routejson';
    private $secret_password = '123456';
    private $test_pub_secret = 'ogw-fat-2048-cfca.der';

    function __construct()
    {
//        $this->app_id = CreateConf::getInstance()->getConf('guangzhouyinlian.app_id');
//        $this->timestamp = time() * 1000;
        return parent::__construct();
    }

    /**
     * 人脸识别验证
     * @return void
     */
    public function checkFaceNew()
    {
        //gnete.upbc.verify.checkFaceNew
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            certNo      身份证
            name        姓名
            img         Base64 编码的活体抓拍的照片数据
        */
    }

    /*
     * 银行卡二要素验证
     */
    public function checkCardTwoEle()
    {
        //gnete.upbc.verify.checkCardTwoEle
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            bankCode      银行代码，支持 3 位或 8 位银行代码,或金融机构代码  不上送则根据卡 BIN 自动识别
            acctNo        账户编号
            name          账户名称
        */
    }

    /*
     * 银行卡三要素验证
     */
    public function checkCardThreeEle()
    {
        //gnete.upbc.verify.checkCardThreeEle
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            bankCode      银行代码，支持 3 位或 8 位银行代码,或金融机构代码  不上送则根据卡 BIN 自动识别
            acctNo        账户编号
            name          账户名称
            certType      证件类型
            certNo        证件号码
        */
    }

    /*
     * 银行卡四要素验证
     */
    public function checkCardFourEle()
    {
        //gnete.upbc.verify.checkCardFourEle
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            bankCode      银行代码，支持 3 位或 8 位银行代码,或金融机构代码  不上送则根据卡 BIN 自动识别
            acctNo        账户编号
            name          账户名称
            certType      证件类型
            certNo        证件号码
            mobile        手机号
        */
    }

    /*
     * 运营商三要素验证
     */
    public function checkTelThreeEle()
    {
        //gnete.upbc.verify. checkTelThreeEle
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            name          姓名
            certType      证件类型
            certNo        证件号码
            mobile        手机号
        */
    }

    /*
     * 身份证二要素验证
     */
    public function checkIdTwoEle()
    {
        $method = 'gnete.upbc.verify.checkIdTwoEle';
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            name          姓名
            certNo        证件号码
        */
        $time      = time();
        $sndDt     = date('YmdHis', $time);
        $merOrdrNo = $this->busiMerno . date('Ymd', $time) . control::randNum(9);
    }

    /*
     * 金融风控查询
     * 711004 车辆历史上理赔情况
     * 711005 车辆历史上过户次数
     * 711006 车辆历史出险次数
     * 711009 商业险保单止期是否大于等当前日期
     * 711010 交强险保单止期是否大于等当前日期
     * 711011 保单第一受益人判定（建行、本人）
     * 711013 是否购买车损险
     * 711014 是否购买盗抢险
     * 711015 是否购买三者险
     * 711016 是否购买交强险
     * 711019 新车购置价
     * 711021 车辆价值判定
     *
     * merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
     * busiId     商户开通的业务 ID
     * bizFunc    业务功能
     * vehicleVerifyInf  验证信息
     *      name        客户名称
     *      userNo      客户标识
     *      certType    证件类型
     *      certNo      证件号码
     *      vin         车架号
     *      licenseNo   号牌号码
     *      areaNo      业务地区码
     *      firstBeneficiary    保单第一受益人
     */
    public function queryInancialBank($postData)
    {
        $method      = 'gnete.upbc.vehicle.queryInancialBank';
        $time        = time();
        $sndDt       = date('YmdHis', $time);
        $merOrdrNo   = $this->busiMerno . date('Ymd', $time) . control::randNum(9);
        $biz_content = [
            'sndDt'     => $sndDt,
            'busiMerNo' => $this->busiMerno,
            'msgBody'   => [
                'busiId'           => '00270001',
                'vehicleVerifyInf' => [
                    'name'      => $postData['name'],           //张万珍',
                    'userNo'    => $postData['userNo'],         //'888888',
                    'certType'  => $postData['certType'],       //'0',
                    'certNo'    => $postData['certNo'],         //'142129195506080532',
                    'vin'       => $postData['vin'],            //'LVSHFC0HH309074',
                    'licenseNo' => $postData['licenseNo'],      //'京08NN2',
                    //                    'areaNo',
                    //                    'firstBeneficiary'
                ],
                'bizFunc'          => $postData['bizFunc'],//'711004',
                'merOrdrNo'        => $merOrdrNo,
            ]
        ];
        list($postData, $header) = $this->rsaData($method, $time, $biz_content);

        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)
            ->send($this->testUrl, $postData, $header, ['enableSSL' => true], 'postjson');
        dingAlarm('金融风控查询', ['$res' => json_encode($res), '$biz_content' => $biz_content]);
        return json_decode($res, true);
    }

    /*
     * 车辆数量查询
     */
    public function queryVehicleCount($postData)
    {
        $method      = 'gnete.upbc.vehicle.queryVehicleCount';
        $time        = time();
        $sndDt       = date('YmdHis', $time);
        $merOrdrNo   = $this->busiMerno . date('Ymd', $time) . control::randNum(9);
        $biz_content = [
            'sndDt'     => $sndDt,
            'busiMerNo' => $this->busiMerno,
            'msgBody'   => [
                'busiId'           => '00270002',
                'vehicleVerifyInf' => [
                    'certNo'   => '150121199110112910',
                    'certType' => '0',
                    'userNo'   => '888888',
                    'name'     => '',
                    'vin'      => ''
                ],
                'bizFunc'          => '721001',
                'merOrdrNo'        => $merOrdrNo,
            ]
        ];
        list($postData, $header) = $this->rsaData($method, $time, $biz_content);
        $res = (new CoHttpClient())
            ->useCache(false)
            ->needJsonDecode(false)
            ->send($this->testUrl, $postData, $header, ['enableSSL' => true], 'postjson');
        dingAlarm('车辆数量查询', ['$res' => json_encode($res)]);
        return json_decode($res, true);
    }

    /*
     * 统一加密
     */
    public function rsaData($method, $time, $biz_content)
    {
        $signArr    = [
            'app_id'      => $this->app_id,
            'method'      => $method,
            'timestamp'   => date('Y-m-d H:i:s', $time),
            'v'           => $this->v,
            'sign_alg'    => $this->sign_alg,
            'biz_content' => json_encode($biz_content),
        ];
        $postArr    = $signArr;
        $content    = http_build_query($signArr);
        $privateKey = openssl_get_privatekey($this->privateKey);
        dingAlarm('车辆数量查询 $privateKey ', ['$privateKey' => $privateKey]);
        openssl_sign($content, $resign, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        //签名转换的byte数组 256
        $signByteArr = $this->getBytes($resign);
        //对签名进行处理，获取发送的签名内容 512位十六进制字符串
        $signArr         = $this->encodeHex($signByteArr);
        $sign            = implode($signArr);
        $postArr['sign'] = $sign;
        $header          = [
            'content-type' => 'text/json;charset=UTF-8'
        ];
        //请求发送内容
        return [http_build_query($postArr), $header];
    }

    /*
     * 车辆车架号查询
     */
    public function queryVehicleVin()
    {
        //gnete.upbc.vehicle.queryVehicleVin
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能  721002
         vehicleVerifyInf       验证信息
             name           客户名称
             userNo         客户标识
             certType       证件类型
             certNo         证件号码
             vin            车架号
         */
    }

    /*
     * 价值最高的车辆车架号查询
     */
    public function queryVehicleVinTop()
    {
        //gnete.upbc.vehicle.queryVehicleVinTop
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能  721003
         vehicleVerifyInf       验证信息
             name           客户名称
             userNo         客户标识
             certType       证件类型
             certNo         证件号码
             vin            车架号
         */
    }

    /*
     * 最近购买的车辆车架号查询
     */
    public function queryVehicleVinRecent()
    {
        //gnete.upbc.vehicle.queryVehicleVinRecent
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能  721004
         vehicleVerifyInf       验证信息
             name           客户名称
             userNo         客户标识
             certType       证件类型
             certNo         证件号码
             vin            车架号
         */
    }

    /*
     * 车架号信息查询
     */
    public function queryVehicleInfo()
    {
        //gnete.upbc.vehicle.queryVehicleInfo
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能
                        721002:车架号查询
                        721003:价值最高车架号查询
                        721004:最近购买车架号查询
         vehicleVerifyInf       验证信息
             name           客户名称
             userNo         客户标识
             certType       证件类型
             certNo         证件号码
             vin            车架号
         */
    }

    /*
     * 二手车信息查询
     */
    public function queryUsedVehicleInfo()
    {
        //gnete.upbc.vehicle.queryUsedVehicleInfo
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能  731001: 最大损失金额查询
         vehicleVerifyInf       验证信息
             userNo         客户标识
             vin            车架号
             licenseNo      号牌号码
             licenseNoType  号牌种类
         */

    }

    /**
     * 字符串转换成字节数组
     * @param  [String] $str
     * @return [byte[]]
     */
    public function getBytes($str)
    {
        $len   = strlen($str);
        $bytes = array();
        for ($i = 0; $i < $len; $i++) {
            if (ord($str[$i]) >= 128) {
                $byte = ord($str[$i]) - 256;
            } else {
                $byte = ord($str[$i]);
            }
            $bytes[] = $byte;
        }
        return $bytes;

    }


    public function encodeHex($data)
    {
        $digits_lower = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f');
        $toDigits     = $digits_lower;
        $len          = count($data);
        $i            = 0;
        $out          = array();
        for ($var = 0; $i < $len; ++$i) {

            $var1 = 240 & $data[$i];

            $index1 = $this->unsignedRight($var1, 4);

            $out[$var] = $toDigits[$index1];

            $var++;
            $index2    = 15 & $data[$i];
            $out[$var] = $toDigits[$index2];
            $var++;
        }


        return $out;
    }

    function unsignedRight($int, $n)
    {
        for ($i = 0; $i < $n; $i++) {
            if ($int < 0) {
                $int >>= 1;
                $int &= PHP_INT_MAX;
            } else {
                $int >>= 1;
            }
        }
        return $int;
    }
}