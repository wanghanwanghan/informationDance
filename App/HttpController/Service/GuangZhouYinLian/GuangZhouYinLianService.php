<?php
namespace App\HttpController\Service\FaYanYuan;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\RequestUtils\StatisticsService;
use App\HttpController\Service\ServiceBase;
use wanghanwanghan\someUtils\control;

class GuangZhouYinLianService extends ServiceBase
{
    private $app_id = '5dc387b32c07871d371334e9c45120ba';
    private $timestamp;
    private $v = '1.0.1';
    private $sign_alg = 5;
    private $busiMerno = '898441650210002';
    private $testUrl = 'https://testapi.gnete.com:9083/routejson';
    private $test_secret = 'mer-fat-905975ed38ff446c91247b1c91a82d41.p12';
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
        $data = [
            'app_id' => $this->app_id,
            'timestamp' => date('Y-m-d H:i:s',$time),
            'v' => $this->v,
            'sign_alg' => $this->sign_alg,
            'method' => $method,
            'biz_content' => $biz_content,
            'sign' => ''
        ];
        $data['sign'] = md5(json_encode($data));
        $res = (new CoHttpClient())->send($this->testUrl, $data);
        dingAlarm('车辆数量查询',[['$data'=>$data],'$res'=>$res]);
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
}