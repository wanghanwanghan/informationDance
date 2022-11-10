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

        //拿结果
        switch ($type) {
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

    //实时ocr查验
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

    "invoiceCode": "051002100204", //发票代码
    "invoiceNumber": "03013525", //发票号码
    "billingDate": "2022-09-29 ",//开票日期
    "totalAmount": "1051.07",//总金额
    "totalTax": "62.03",//总税额
    "invoiceType": "04",//发票类型
    "state": "0",//发票状态
    "salesTaxNo": "91510104723445820B",//卖方税号
    "salesTaxName": "成都万科物业服务有限公司",//卖方名称
    "purchaserTaxNo": "91510106MA7FM5BL90",//买方税号
    "purchaserName": "四川账三丰互联网科技有限公司"//买方名称
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
    "invoiceCode": "051002100204",//发票代码
    "invoiceNumber": "03013525",//发票号码
    "billingDate": "2022-09-29 ",//开票日期
    "amount": "1051.07",//开票金额
    "tax": "62.03",//含税单价？
    "invoiceType": "04",//规格型号？
    "goodName": "-",//商品名？
    "taxRate": "0",//税率？
    "unitPrice": "-",//单价？
    "quantity": "1",//数量
    "specificationModel": "-"//？
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
    "declarationDate": "2022-10-24 00:00:00",//申报日期
    "endDate": "2022-09-30 00:00:00",//所属时期止
    "levyProjectName": "2021版企业所得税A类申报",//征收项目
    "accumulativeAmount": 0E-8,//累计金额
    "projectType": "预缴税款计算",//项目类型
    "projectSubType": "",//项目父类型
    "currentAmount": 0.0,//本期金额(2015版专有)
    "beginDate": "2022-07-01 00:00:00",//所属时期起
    "sequence": 5,//顺序
    "tableType": "A",
    "columnSequence": "5",//栏次
    "projectNameCode": "070127",//项目代码
    "bureau": "SICHUAN",//所属税务局
    "taxNo": "TAX_NO_0120221110113032OYH",//授权批次号
    "projectName": "减：不征税收入",//项目名称
    "deadline": null,
    "taxpayerId": "91510106MA7FM5BL90" //纳税识别号

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
                'getAuthentication_res'=>$res,
                'getAuthentication_param'=>[
                    '$entName'=>$entName,
                    '$callback'=>$callback,
                    '$orderNo'=>$orderNo
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
