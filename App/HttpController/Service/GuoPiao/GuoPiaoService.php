<?php

namespace App\HttpController\Service\GuoPiao;

use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\InvoiceIn;
use App\HttpController\Models\Api\InvoiceOut;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\ServiceBase;

class GuoPiaoService extends ServiceBase
{
    public $taxNo;
    public $key;
    public $keyTest;
    public $url;
    public $urlTest;

    function __construct()
    {
        parent::__construct();

        $this->taxNo = '91110108MA01KPGK0L';
        $this->key = CreateConf::getInstance()->getConf('guopiao.key');
        $this->keyTest = CreateConf::getInstance()->getConf('guopiao.keyTest');
        $this->url = CreateConf::getInstance()->getConf('guopiao.url');
        $this->urlTest = CreateConf::getInstance()->getConf('guopiao.urlTest');

        $this->guopiao_url = CreateConf::getInstance()->getConf('guopiao.guopiao_url');
        $this->client_id = CreateConf::getInstance()->getConf('guopiao.client_id');
        $this->client_secret = CreateConf::getInstance()->getConf('guopiao.client_secret');

        return true;
    }

    private function encrypt($str, $isTest = false)
    {
        $isTest === true ? $key = $this->keyTest : $key = $this->key;
        return openssl_encrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }

    private function decrypt($str, $isTest = false)
    {
        $isTest === true ? $key = $this->keyTest : $key = $this->key;
        return openssl_decrypt($str, 'aes-128-ecb', $key, OPENSSL_RAW_DATA);
    }

    private function checkResp($res, $type)
    {
        if (isset($res['data']['total']) &&
            isset($res['data']['pageSize']) &&
            isset($res['data']['currentPage'])) {
            $res['Paging'] = [
                'page' => $res['data']['currentPage'],
                'pageSize' => $res['data']['pageSize'],
                'total' => $res['data']['total'],
            ];
        } else {
            $res['Paging'] = null;
        }

        if (isset($res['coHttpErr'])) return $this->createReturn(500, $res['Paging'], [], 'co请求错误');

        $res['code'] - 0 === 0 ? $res['code'] = 200 : $res['code'] = 600;
        if( in_array($type,['checkInvoice','realTimeRecognize']) ){
            $res['failCode'] - 0 === 0 ? $res['code'] = 200 : $res['code'] = 600;
            $res['msg'] = $res['result']?:"success"  ;
        }

        //拿结果
        switch ($type) {
            case 'realTimeRecognize':
                $res['Result'] = $res['dataDetails'];
                break;
            case 'checkInvoice':
                $res['Result'] = $res;
                break;
            case 'getReceiptDetailByClient':
            case 'getReceiptDetailByCert':
                $res['Result'] = $res['data']['invoices'];
                break;
            case 'getInvoiceOcr':
                $res['Result'] = empty($res['data']) ? null : current($res['data']);
                break;
            case 'getTaxInvoiceUpgrade':
            case 'getInvoiceMain':
            case 'getInvoiceGoods':
            case 'getEssential':
            case 'getInvoiceCheck':
                $res['Result'] = empty($res['data']) ? null : $res['data'];
                break;
            case 'getIncometaxMonthlyDeclaration':
            case 'getIncometaxAnnualReport':
            case 'getFinanceIncomeStatementAnnualReport':
            case 'getFinanceIncomeStatement':
            case 'getFinanceBalanceSheetAnnual':
            case 'getFinanceBalanceSheet':
            case 'getVatReturn':
                $res['Result'] = is_string($res['data']) ? jsonDecode($res['data']) : $res['data'];
                break;
            default:
                $res['Result'] = null;
        }

        return $this->createReturn($res['code'], $res['Paging'], $res['Result'], $res['msg'] ?? null);
    }

    //进项销项发票详情 客户端（税盘）专用
    function getInOrOutDetailByClient($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['currentPage'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/getClientInvoices';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进项销项发票详情 证书专用
    function getInOrOutDetailByCert($code, $dataType, $startDate, $endDate, $page, $pageSize)
    {
        $param['taxNumber'] = $code;
        $param['invoiceType'] = '';//查询全部种类
        $param['dataType'] = $dataType;//1是进项，2是销项
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page;
        $param['pageSize'] = $pageSize;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/invoiceCollection';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进项销项发票详情授权 证书专用
    function sendCertificateAccess($delegateCert, $fileType, $taxNature, $taxAuthorityCode, $taxAuthorityName, $certificate)
    {
        $param['delegateCert'] = $delegateCert;
        $param['fileType'] = $fileType - 0;
        $param['taxNature'] = $taxNature - 0;
        $param['taxAuthorityCode'] = $taxAuthorityCode;
        $param['taxAuthorityName'] = $taxAuthorityName;
        $param['certificate'] = $certificate;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/certificateAccess';

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //实时ocr查验 readyToSendV2
    function getInvoiceOcr($image)
    {
        //图片steam的base64编码
        $body = $param = [];
        $param['content'] = $image;
        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/realTimeRecognize';

        $res = $this->readyToSend($api_path, $body, false, true, true);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    /***
    参数 参数类型 参数说明 是否必填 备注
    fileName String 文件名 否 支持常规图片、PDF格式以及ofd格式文件
    base64Content String base64字符串 否 base64Content和imageUrl任选其一必填，如果二者均不为空，优先识别imageUrl。
    imageUrl String 图片链接地址 否
     ***/
    function getInvoiceOcrV2($fileName,$base64Content,$imageUrl,$showLog = false)
    {
        $data = [
            "fileName" => "invoice.png",
            "base64Content" => "",
            "imageUrl" => "https://api.meirixindong.com/Static/Temp/invoice.png",
        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                'api 实时识别图片' => [
                    '文件名' => $fileName,
                    'base64字符串' => $base64Content,
                    '图片链接地址' => $imageUrl
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        $url = $this->guopiao_url.'api/ocr/realTimeRecognize';
        //$url = 'http://ivs.fapiao.com/mars/api/ocr/realTimeRecognize';

        ksort($data);

        $date = gmdate('D, d M Y H:i:s', time()+3600 * 8)." GMT";
        $rand = strtolower(self::guid());

        $accept = '*/*';
        $contentType= 'application/json; charset=utf-8';

        $customHeaderStr = "x-mars-api-version:20190618\nx-mars-signature-nonce:$rand\n";
        //$ContentMD5 = base64_encode(md5(json_encode($data,JSON_UNESCAPED_UNICODE)));
        $httpHeaderStr = "POST\n$accept\nnull\n$contentType\n$date\n";
        $stringToSign = $httpHeaderStr.$customHeaderStr.$url;

        $Signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->client_secret, true));

        $headers = [
            'date' => $date,
            'signature' => 'mars '.$this->client_id.':'.$Signature,
            'x-mars-api-version' => '20190618',
            'x-mars-signature-nonce'=>$rand,
            'Content-Type' => $contentType
        ];

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send(
            $url, $data,$headers,[],"POSTJSON"
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '国票-发起请求' => [
                    'url' => $url,
                    'data' => $data,
                    'headers' => $headers,
                    '返回' => $res,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        return $res;

        //return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }


    function getRequestSignature($rand,$contentType,$date,$accept,$url,$method){
        $customHeaderStr = "x-mars-api-version:20190618\nx-mars-signature-nonce:$rand\n";

        //$ContentMD5 = base64_encode(md5(json_encode($data,JSON_UNESCAPED_UNICODE)));
        $httpHeaderStr = "$method\n$accept\nnull\n$contentType\n$date\n";
        $stringToSign = $httpHeaderStr.$customHeaderStr.$url;

        $Signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->client_secret, true));

        return $Signature;
    }

    function  getGetRequestHeader($accept,$rand,$date,$url){
        $customHeaderStr = "x-mars-api-version:20190618\nx-mars-signature-nonce:$rand\n";
        $httpHeaderStr = "GET\n$accept\nnull\nnull\n$date\n";
        $stringToSign = $httpHeaderStr.$customHeaderStr.$url;
        $Signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->client_secret, true));
        $headers = [
            'date:'.$date,
            'signature:mars '.$this->client_id.':'.$Signature,
            'x-mars-api-version:20190618',
            'x-mars-signature-nonce:'.$rand
        ];

        $headers = [
            'date' => $date,
            'signature' =>'mars '.$this->client_id.':'.$Signature,
            'x-mars-api-version' => '20190618',
            'x-mars-signature-nonce' =>$rand
        ];

        return $headers;
    }

    function getRequestHeaders($url,$method){

        $date = self::getRequestDate();
        $rand = strtolower(self::guid());

        $accept =  self::getHeaderAccepet();
        $contentType= self::getContentType();

        $Signature = $this->getRequestSignature($rand,$contentType,$date,$accept,$url,$method);

        if($method == "POST"){
            $headers = [
                'date' => $date,
                'signature' => 'mars '.$this->client_id.':'.$Signature,
                'x-mars-api-version' => '20190618',
                'x-mars-signature-nonce'=>$rand,
                'Content-Type' => $contentType
            ];
        }

        if($method == "GET"){
            $headers = [
                'date' => $date,
                'signature' => 'mars '.$this->client_id.':'.$Signature,
                'x-mars-api-version' => '20190618',
                'x-mars-signature-nonce'=>$rand,
            ];
        }


        return $headers;
    }

    function realTimeRecognize($fileName,$base64Content,$imageUrl,$showLog = false)
    {
        $data = [
            "fileName" => $fileName,
            "base64Content" => $base64Content,
            "imageUrl" => $imageUrl,
        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                'api实时识别图片' => [
                    '文件名' => $fileName,
                    'base64字符串' => $base64Content,
                    '图片链接地址' => $imageUrl
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        $url = $this->guopiao_url.'api/ocr/realTimeRecognize';
        ksort($data);

        $headers = $this->getRequestHeaders($url,"POST");

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(true)->send(
            $url, $data,$headers,[],"POSTJSON"
        );
        $newRes = $res;
        if($this->checkRespFlag){
            $newRes =  $this->checkResp($res, __FUNCTION__) ;
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                '国票-发起请求' => [
                    'url' => $url,
                    'data' => $data,
                    'headers' => $headers,
                    '返回' => $res,
                    'checkResp返回' => $newRes,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        return $newRes;
    }

    /***

    名称 类 型 是否必填 说明 备注
    flowId String 否 客户请求流水 推荐格式：YYYYMMDD+发票代码+发票号码+8位随机数
    invoiceCode String 否 发票代码 10或12位的发票代码(发票类型非09或90不可为空,即非全电发票时)
    invoiceNumber String 是 发票号码 8位数字的发票号码
    billingDate String 是 开票日期 格式为YYYY-MM-DD
    totalAmount String 否 发票金额 发票类型为 01、03、15、20时不可为空；01、03、20填写发票不含税金额；15填写发票车价合计
    ；09、90填写发票价税合计
    checkCode String 否 校验码 发票校验码后6位。发票类型为04、10、11、14时此项不可为空。
    $data = [
        "invoiceCode" =>$invoiceCode,
        "invoiceNumber" => $invoiceNumber,
        "billingDate" => $billingDate,
        "totalAmount" => $totalAmount,
        "checkCode" => $checkCode,
        "flowId" => date("YYYYMMDD")."_".$invoiceCode.rand(10000000,99999999),
    ];
     ***/

    static function getHeaderAccepet(){
        return '*/*';
    }

    static function getContentType(){
        return 'application/json; charset=utf-8';
    }

    static function getRequestDate(){
        return  gmdate('D, d M Y H:i:s', time()+3600 * 8)." GMT";
    }
    function checkInvoice($invoiceCode,$invoiceNumber,$billingDate,$totalAmount,$checkCode){
        $invoiceNumber = "021022200104";
        $data = [
            "invoiceCode" => "021022200104",
            "invoiceNumber" => "03660056",
            "billingDate" => "2022-11-25",
            "totalAmount" => "3301.89",
            "checkCode" => "203465",
            "flowId" => date("YYYYMMDD")."_".$invoiceCode.rand(10000000,99999999),
        ];

        ksort($data);
        $str = http_build_query($data);

        $url = $this->guopiao_url.'api/check/invoice?'.$str;

        $accept = self::getHeaderAccepet();

        $date =  self::getRequestDate();
        $rand = strtolower(self::guid()); 

        $headers = self::getGetRequestHeader($accept,$rand,$date,$url);

        $res = (new CoHttpClient())->useCache(false)->needJsonDecode(true)->send(
            $url, [],$headers,[],"GET"
        );

        $newRes = $res;
        if($this->checkRespFlag){
            $newRes =  $this->checkResp($res, __FUNCTION__) ;
        }

        CommonService::getInstance()->log4PHP(
            json_encode([
                '国票-发起请求' => [
                    'url' => $url,
                    'data' => $data,
                    'headers' => $headers,
                    '返回' => $res,
                    'checkResp返回' => $newRes,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        return $newRes;
    }
    /**
     * uuid生成
     * @return string
     */
    public static function guid(){
        if (function_exists('com_create_guid')){
            return com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid =substr($charid, 0, 8).$hyphen
                .substr($charid, 8, 4).$hyphen
                .substr($charid,12, 4).$hyphen
                .substr($charid,16, 4).$hyphen
                .substr($charid,20,12)
            ;
            return $uuid;
        }
    }

    //实时查验
    function getInvoiceCheck($data)
    {
        $body = [];
        $body['param'] = $data;
        $body['taxNo'] = $this->taxNo;

        $res = $this->readyToSend('invoice/checkInvoice', $body, false, true, true);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业授权认证
    function getAuthentication($entName, $callBackUrl, $orderNo)
    {
        $data = [
            'taxNo' => $this->taxNo,
            'companyName' => $entName,
            'callBackUrl' => $callBackUrl,
            'orderNo' => $orderNo,
        ];

        $api_path = 'http://api.enterkey.cn:9200/data/information/getAuthentication';

        $res = $this->readyToSend($api_path, $data, false, false);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票统计查询 目前不能用
    function getTaxInvoice($code, $start, $end)
    {
        $param['taxNumber'] = $code;
        $param['startDate'] = $start;
        $param['endDate'] = $end;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项月度发票统计查询
    function getTaxInvoiceUpgrade($code, $start, $end)
    {
        $param['taxNumber'] = $code;
        $param['startDate'] = $start;
        $param['endDate'] = $end;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票信息 信动专用

    /**
     *
     * "invoiceCode": "051002100204", //发票代码
     * "invoiceNumber": "03013525", //发票号码
     * "billingDate": "2022-09-29 ",//开票日期
     * "totalAmount": "1051.07",//总金额
     * "totalTax": "62.03",//总税额
     * "invoiceType": "04",//发票类型
     * "state": "0",//发票状态
     * "salesTaxNo": "91510104723445820B",//卖方税号
     * "salesTaxName": "成都万科物业服务有限公司",//卖方名称
     * "purchaserTaxNo": "91510106MA7FM5BL90",//买方税号
     * "purchaserName": "四川账三丰互联网科技有限公司"//买方名称
     */
    function getInvoiceMain($code, $dataType, $startDate, $endDate, $page)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType - 0;
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page - 0;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //进销项发票商品明细 信动专用 $dataType：0  $dataType：1

    /**
     * "invoiceCode": "051002100204",//发票代码
     * "invoiceNumber": "03013525",//发票号码
     * "billingDate": "2022-09-29 ",//开票日期
     * "amount": "1051.07",//开票金额
     * "tax": "62.03",//含税单价？
     * "invoiceType": "04",//规格型号？
     * "goodName": "-",//商品名？
     * "taxRate": "0",//税率？
     * "unitPrice": "-",//单价？
     * "quantity": "1",//数量
     * "specificationModel": "-"//？
     */
    function getInvoiceGoods($code, $dataType, $startDate, $endDate, $page)
    {
        $param['taxNumber'] = $code;
        $param['dataType'] = $dataType - 0;
        $param['startDate'] = $startDate;
        $param['endDate'] = $endDate;
        $param['page'] = $page - 0;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业税务基本信息查询

    /**
     * essential    LIST    基本信息-纳税信用等级
     * overdueFine    Text    近12个月滞纳金次数(次)（废弃字段）
     * owingType    Text    是否欠税（是/否）
     * payTaxes    Text    纳税状态 = 正常/异常
     * regulations    Text    违章稽查记录（条）
     * nature    Text    纳税人性质
     *
     * essential:
     * 名称    类型    说明
     * creditLevel    Text    税务征信等级，枚举值[A、B、C、D、M、不参评、暂无、该纳税人还未终审完成]
     * year    string(date-time)    年份
     * taxpayerId    Text    纳税人识别号
     * creditPoint    Text    评价分数
     */

    function getEssential($code)
    {
        $param['taxNumber'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业所得税-月（季）度申报表查询

    /**
     * "declarationDate": "2022-10-24 00:00:00",//申报日期
     * "endDate": "2022-09-30 00:00:00",//所属时期止
     * "levyProjectName": "2021版企业所得税A类申报",//征收项目
     * "accumulativeAmount": 0E-8,//累计金额
     * "projectType": "预缴税款计算",//项目类型
     * "projectSubType": "",//项目父类型
     * "currentAmount": 0.0,//本期金额(2015版专有)
     * "beginDate": "2022-07-01 00:00:00",//所属时期起
     * "sequence": 5,//顺序
     * "tableType": "A",
     * "columnSequence": "5",//栏次
     * "projectNameCode": "070127",//项目代码
     * "bureau": "SICHUAN",//所属税务局
     * "taxNo": "TAX_NO_0120221110113032OYH",//授权批次号
     * "projectName": "减：不征税收入",//项目名称
     * "deadline": null,
     * "taxpayerId": "91510106MA7FM5BL90" //纳税识别号
     *
     */
    function getIncometaxMonthlyDeclaration($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);
//        CommonService::getInstance()->log4PHP(json_encode(
//            [
//                __CLASS__ ,
//                'getIncometaxMonthlyDeclaration'=>[
//                    '$api_path'=>$api_path,
//                    '$body'=>$body,
//                    '$res'=>$res
//                ],
//            ]
//        ));
        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //企业所得税-年报查询
    function getIncometaxAnnualReport($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //利润表 --年报查询
    function getFinanceIncomeStatementAnnualReport($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //利润表查询

    /**
     * "currentMonthAmount",//本月累计金额
     * "declarationDate",//申报日期
     * "endDate",//所属时期止
     * "levyProjectName",//征收项目
     * "reportType",
     * "beginDate",//所属时期起
     * "sequence",//顺序
     * "columnSequence",//栏次
     * "currentYearAccumulativeAmount",//本年累计金额
     * "projectNameCode",//项目代码
     * "taxNo",//授权批次号
     * "projectName",//项目名称
     * "taxpayerId",//纳税识别号
     * "SQJE"
     */
    function getFinanceIncomeStatement($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //资产负债表-年度查询
    function getFinanceBalanceSheetAnnual($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //资产负债表查询
    function getFinanceBalanceSheet($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //增值税申报表查询
    function getVatReturn($code)
    {
        $param['taxpayerId'] = $code;

        $body['param'] = $param;
        $body['taxNo'] = $this->taxNo;

        $api_path = 'invoice/' . __FUNCTION__;

        $res = $this->readyToSend($api_path, $body);

        return $this->checkRespFlag ? $this->checkResp($res, __FUNCTION__) : $res;
    }

    //深度报告临时用的
    function getReceiptDataTest($code, $type)
    {
        if ($type === 'in') {
            $in = InvoiceIn::create()->where('purchaserTaxNo', $code)->all();
            return obj2Arr($in);
        } elseif ($type === 'out') {
            $out = InvoiceOut::create()->where('salesTaxNo', $code)->all();
            return obj2Arr($out);
        } elseif ($type === 'getCode') {
            return InvoiceIn::create()->where('purchaserName', $code)->get()->purchaserTaxNo;
        } else {
            return [];
        }
    }

    //统一发送
    private function readyToSend($api_path, $body, $isTest = false, $encryption = true, $zwUrl = false)
    {
        if (preg_match('/^http/', $api_path)) {
            $url = $api_path;
        } elseif ($isTest) {
            $url = $this->urlTest . $api_path;
        } else {
            $url = $this->url . $api_path;
        }

        if ($encryption) {
            $param = $body['param'];
            $json_param = jsonEncode($param);
            $encryptedData = $this->encrypt($json_param, $isTest);
            $base64_str = base64_encode($encryptedData);
            $body['param'] = $base64_str;

            if ($zwUrl) {
                $url = 'http://api.zoomwant.com:50001/api/' . $api_path;
            }

            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
            $res = base64_decode($res);
            $res = $this->decrypt($res, $isTest);
            return jsonDecode($res);
        } else {
            return (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
        }
    }

    private function postGuoPiao()
    {



    }

    private function readyToSendV2($api_path, $body, $isTest = false, $encryption = true, $zwUrl = false)
    {
        if (preg_match('/^http/', $api_path)) {
            $url = $api_path;
        } elseif ($isTest) {
            $url = $this->guopiao_url . $api_path;
        } else {
            $url = $this->guopiao_url . $api_path;
        }

        if ($encryption) {
            $param = $body['param'];
            $json_param = jsonEncode($param);
            $encryptedData = $this->encrypt($json_param, $isTest);
            $base64_str = base64_encode($encryptedData);
            $body['param'] = $base64_str;

            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($this->guopiao_url, $body);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '国票-发起请求' => [
                        'url' => $this->guopiao_url,
                        'body' => $body,
                        '返回' => $res,
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );

            $res = base64_decode($res);
            $res = $this->decrypt($res, $isTest);
            return jsonDecode($res);
        } else {
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '国票-发起请求' => [
                        'url' => $url,
                        'body' => $body,
                        '返回' => $res,
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );

            return $res;
        }
    }
    private function readyToSendV3($api_path, $body, $isTest = false, $encryption = true, $zwUrl = false)
    {
        if (preg_match('/^http/', $api_path)) {
            $url = $api_path;
        } elseif ($isTest) {
            $url = $this->guopiao_url . $api_path;
        } else {
            $url = $this->guopiao_url . $api_path;
        }

        if ($encryption) {
            $param = $body['param'];
            $json_param = jsonEncode($param);
            $encryptedData = $this->encrypt($json_param, $isTest);
            $base64_str = base64_encode($encryptedData);
            $body['param'] = $base64_str;

            if ($zwUrl) {
                $url = 'http://api.zoomwant.com:50001/api/' . $api_path;
            }

            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($this->guopiao_url, $body);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '国票-发起请求' => [
                        'url' => $this->guopiao_url,
                        'body' => $body,
                        '返回' => $res,
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );

            $res = base64_decode($res);
            $res = $this->decrypt($res, $isTest);
            return jsonDecode($res);
        } else {
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(false)->send($url, $body);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '国票-发起请求' => [
                        'url' => $url,
                        'body' => $body,
                        '返回' => $res,
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );

            return $res;
        }
    }

    function getAuthenticationManage(
        $entName,
        $phone
    ): bool
    {

        if (empty($callback)) {
            $callback = "https://api.meirixindong.com/api/v1/user/addAuthEntName?entName={$entName}&phone={$phone}";
        }

        $orderNo = $phone . time();

        $res = (new GuoPiaoService())->getAuthentication($entName, $callback, $orderNo);
        CommonService::getInstance()->log4PHP(json_encode(
            [
                'getAuthentication_res' => $res,
                'getAuthentication_param' => [
                    '$entName' => $entName,
                    '$callback' => $callback,
                    '$orderNo' => $orderNo
                ],
            ]
        ));
        $res = jsonDecode($res);

        !(isset($res['code']) && $res['code'] == 0) ?: $res['code'] = 200;

        //添加授权信息
        try {
            $check = AuthBook::create()->where([
                'phone' => $phone, 'entName' => $entName, 'code' => $code, 'type' => 2
            ])->get();
            if (empty($check)) {
                AuthBook::create()->data([
                    'phone' => $phone,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,//深度报告，发票数据
                    'remark' => $orderNo
                ])->save();
            } else {
                $check->update([
                    'phone' => $phone,
                    'entName' => $entName,
                    'code' => $code,
                    'status' => 1,
                    'type' => 2,
                    'remark' => $orderNo
                ]);
            }
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (strpos($res['data'], '?url=')) {
            $arr = explode('?url=', $res['data']);
            $res['data'] = 'https://api.meirixindong.com/Static/vertify.html?url=' . $arr[1];
        }

        return $this->writeJson($res['code'], null, $res['data'], $res['message']);
    }
}
