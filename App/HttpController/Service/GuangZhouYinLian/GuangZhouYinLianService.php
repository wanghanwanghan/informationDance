<?php
namespace App\HttpController\Service\GuangZhouYinLian;

use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class GuangZhouYinLianService extends ServiceBase
{
    private $app_id = '5dc387b32c07871d371334e9c45120ba';
    private $timestamp;
    private $v = '1.0.1';
    private $sign_alg = 1;
    private $busiMerno = '898441650210002';
    private $testUrl = 'https://testapi.gnete.com:9083/routejson';
    private $privateKey = 'bussiness_private_dev.pem';// 'mer-fat-905975ed38ff446c91247b1c91a82d41.p12'; //'';bussiness_private.pem   bussiness_private_dev.pem
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
    public function checkFaceNew(){
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
    public function checkCardTwoEle(){
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
    public function checkCardThreeEle(){
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
    public function checkCardFourEle(){
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
    public function checkTelThreeEle(){
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
    public function checkIdTwoEle(){
        //gnete.upbc.verify.checkIdTwoEle
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         VerifyInf  云从验证信息
            name          姓名
            certNo        证件号码
        */
    }

    /*
     * 金融风控查询
     */
    public function queryInancialBank(){
        // gnete.upbc.vehicle.queryInancialBank
        /* merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
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

    }

    /*
     * 车辆数量查询
     */
    public function queryVehicleCount($postData){
        $method = 'gnete.upbc.vehicle.queryVehicleCount';
        /*
         merOrdrNo  商户交易订单号，需保证在商户端不重复; 格式：15 位商户号+8 位交易日期+9 位序数字列号
         busiId     商户开通的业务 ID
         bizFunc    业务功能  721001
         vehicleVerifyInf       验证信息
             name           客户名称
             userNo         客户标识
             certType       证件类型
             certNo         证件号码
             vin            车架号
         */
        $time = time();
        $sndDt = date('YmdHis',$time);
        $merOrdrNo = $this->busiMerno.$sndDt.control::randNum(9);;
        $biz_content = [
            'sndDt' => $sndDt,
            'busiMerNo' => $this->busiMerno,
            'msgBody' => [
                'busiId' => '00270002',
                'vehicleVerifyInf' => [
                    'certNo' => '140624198802132541',
                    'certType' => '0',
                    'userNo' => '',
                    'name' => '',
                    'vin' => ''
                ],
                'bizFunc' => '721001',
                'merOrdrNo' => $merOrdrNo,
            ]
        ];

        $signArr = [
            'app_id' => $this->app_id,
            'method' => $method,
            'timestamp' => date('Y-m-d H:i:s',$time),
            'v' => $this->v,
            'sign_alg' => $this->sign_alg,
            'biz_content' => json_encode($biz_content),
        ];
        $postArr = $signArr;
        $content = http_build_query($signArr);
        $privateKey = openssl_get_privatekey(file_get_contents(RSA_KEY_PATH .$this->privateKey));
//        openssl_pkcs12_read(file_get_contents(RSA_KEY_PATH .$this->privateKey),$privateKey,123456);
//        openssl_sign($content, $resign, $privateKey['pkey'], OPENSSL_ALGO_MD5);
        dingAlarm('车辆数量查询 $privateKey ',['$privateKey'=>$privateKey]);
        openssl_sign($content, $resign, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        //签名转换的byte数组 256
        $signByteArr = $this->getBytes($resign);
        //对签名进行处理，获取发送的签名内容 512位十六进制字符串
        $signArr = $this->encodeHex($signByteArr);
        $sign = implode($signArr);
//        dingAlarm('车辆数量查询',['$sign'=>$sign]);

        $postArr['sign'] = $sign;
        //请求发送内容
        $postData = http_build_query($postArr);
        $header = [
            'content-type' => 'text/json;charset=UTF-8'
        ];
        dingAlarm('车辆数量查询',['$data'=>json_encode($postData),'url'=>$this->testUrl]);
        $res = $this->request($this->testUrl,$postData);
//        $res = (new CoHttpClient())->useCache(false)->send($this->testUrl, $postData,$header);
        dingAlarm('车辆数量查询',['$res'=>json_encode($res)]);
        return $res;
    }

    function request($url,$data){
        $header = ["Content-type:text/json;charset=utf-8"];

        $curl=curl_init();

        //根据是否发送https请求设置是否进行证书检测
//        if($isHttps){
//            //没有证书，规避证书检测
//            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);//规避SSL验证
//            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, false);//跳过HOST验证
//        }else{

            curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,false);
            curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, false);
//        }
        curl_setopt($curl,CURLOPT_URL,$url);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true); // 使用自动跳转
        curl_setopt($curl, CURLOPT_AUTOREFERER, true); // 自动设置Referer
        curl_setopt($curl, CURLOPT_POST, true); // 发送一个常规的Post请求
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data); // Post提交的数据包
        curl_setopt($curl, CURLOPT_TIMEOUT, 10000); // 设置超时限制防止死循环
        curl_setopt($curl, CURLOPT_HEADER, false); // 显示返回的Header区域内容
        //curl_setopt($curl, CURLOPT_PROXY, '127.0.0.1:8888');
        //设置请求头
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);//设置请求数据
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $res=curl_exec($curl);
        if($res==false){
            echo "请求发送失败:". curl_error($curl)."\n";
        }
        curl_close($curl);

        return $res;
    }


    /*
     * 车辆车架号查询
     */
    public function queryVehicleVin(){
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
    public function queryVehicleVinTop(){
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
    public function queryVehicleVinRecent(){
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
    public function queryVehicleInfo(){
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
    public function queryUsedVehicleInfo(){
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
    public function getBytes($str) {
        $len = strlen($str);
        $bytes = array();
        for($i=0;$i<$len;$i++) {
            if(ord($str[$i]) >= 128){
                $byte = ord($str[$i]) - 256;
            }else{
                $byte = ord($str[$i]);
            }
            $bytes[] =  $byte ;
        }
        return $bytes;

    }


    public function encodeHex($data){
        $digits_lower = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e', 'f' );
        $toDigits=$digits_lower;
        $len=count($data);
        $i=0;
        $out=array();
        for($var=0;$i<$len;++$i){

            $var1=240&$data[$i];

            $index1=$this->unsignedRight($var1,4);

            $out[$var]=$toDigits[$index1];

            $var++;
            $index2=15&$data[$i];
            $out[$var]=$toDigits[$index2];
            $var++;
        }


        return $out;
    }

     function unsignedRight($int, $n){
        for ($i=0; $i < $n; $i++) {
            if( $int < 0 ){
                $int >>= 1;
                $int &= PHP_INT_MAX;
            }else{
                $int >>= 1;
            }
        }
        return $int;
    }
}