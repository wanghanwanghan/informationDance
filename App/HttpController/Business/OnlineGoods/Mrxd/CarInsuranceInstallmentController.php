<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
use App\HttpController\Models\AdminV2\CarInsuranceInstallmentMatchedRes;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\Api\AuthBook;
use App\HttpController\Models\Api\CarInsuranceInfo;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use wanghanwanghan\someUtils\control;

class CarInsuranceInstallmentController extends \App\HttpController\Business\OnlineGoods\Mrxd\ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function sendSmsForCarInsurance(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;

        if (empty($phone) ){
            return $this->writeJson(201, null, null, '手机号不能是空');
        }

        //重复提交校验
        if(
            !ConfigInfo::setRedisNx('CarInsurance_sendSms',3)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }


        $res = OnlineGoodsUser::sendMsg($phone,'CarInsurance');
        if($res['failed']){
            return $this->writeJson(201, null, [],  $res['msg']);
        }

        return $this->writeJson(
            200,[ ] ,$res,
            '成功',
            true,
            []
        );
    }

    function getCodeByName(): bool
    {
        $requestData = $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [
                'ent_name' => [
                    'not_empty' => 1,
                    'field_name' => 'ent_name',
                    'err_msg' => '企业名必填',
                ],
            ],
            $requestData
        );

        if (
            !$checkRes['res']
        ) {
            return $this->writeJson(203, [], [], $checkRes['msgs'], true, []);
        }

        $res = CompanyBasic::findByName($requestData['ent_name']);
        $res = $res?$res->toArray():[];

        return $this->writeJson(200, [],  $res['UNISCID'],[], true, []);
    }

    function authForCarInsurance(): bool
    {
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [
                'ent_name' => [
                    'not_empty' => 1,
                    'field_name' => 'ent_name',
                    'err_msg' => '企业名必填',
                ],
                'code' => [
                    'not_empty' => 1,
                    'field_name' => 'code',
                    'err_msg' => '验证码必填',
                ],
                'legal_phone' => [
                    'not_empty' => 1,
                    'field_name' => 'legal_phone',
                    'err_msg' => '法人手机号必填',
                ],
                'legal_person' => [
                    'not_empty' => 1,
                    'field_name' => 'legal_person',
                    'err_msg' => '法人必填',
                ],
                'legal_person_id_card' => [
                    'not_empty' => 1,
                    'field_name' => 'legal_person_id_card',
                    'err_msg' => '法人身份证必填',
                ],
                'social_credit_code' => [
                    'not_empty' => 1,
                    'field_name' => 'social_credit_code',
                    'err_msg' => '企业信用代码必填必填',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        //法人手机验证码验证
        $redis_code = OnlineGoodsUser::getRandomDigit($requestData['legal_phone'],'CarInsurance_sms_code_');
        if($redis_code != $requestData['code']){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'redis code not equal'=> [
                        '$redis_code'=>$redis_code,
                        'code'=>$requestData['code'],
                    ]
                ])
            );
            return $this->writeJson(203,[ ] , [], '验证码已过期', true, []);
        }

        //请求微风起 获取授权地址
        $callback = "https://api.meirixindong.com/api/v1/user/addAuthEntName?entName=".$requestData['ent_name']
            ."&phone=".$requestData['legal_phone'];
        $orderNo = control::getUuid(20);
        $res_raw = (new GuoPiaoService())->getAuthentication($requestData['ent_name'], $callback, $orderNo);
        $res = jsonDecode($res_raw);
        !(isset($res['code']) && $res['code'] == 0) ?: $res['code'] = 200;

        //保存到授权表
        $authId = AuthBook::addRecordV2(
            [
                'phone' =>  $requestData['legal_phone'],
                'entName' => $requestData['ent_name'],
                'code' => $requestData['social_credit_code'],
                'status' => 1,
                'type' => 2,//深度报告，发票数据
                'remark' => $orderNo
            ]
        );

//        if (strpos($res['data'], '?url=')) {
//            $arr = explode('?url=', $res['data']);
//            $res['data'] = 'https://api.meirixindong.com/Static/vertify.html?url=' . $arr[1];
//        }
        CarInsuranceInstallment::addRecordV2(
            [
                'user_id' => $this->loginUserinfo['id'],
                'product_id' => $requestData['product_id']?:0,
                'ent_name' => $requestData['ent_name'],
                'legal_phone' => $requestData['legal_phone'],
                'legal_person' => $requestData['legal_person'],
                'legal_person_id_card' => $requestData['legal_person_id_card'],
                'social_credit_code' => $requestData['social_credit_code'],
                'auth_id' => $authId,
                'order_no' => $orderNo,
                'url' => $res['data']?:'',
                'raw_return' => $res_raw,
                'status' => $requestData['status']?:1,
                'created_at' => time(),
                'updated_at' => time(),
            ]
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'authForCarInsurance data'=> $res['data']

            ])
        );
        return $this->writeJson(
            200,[ ] ,
            //CommonService::ClearHtml($res['body']),
            $res['data'],
            '成功',
            true,
            []
        );
    }

    //匹配结果
    function getMatchedRes(): bool
    {
        $requestData =  $this->getRequestData();
        $page= $requestData['page']?:1;
        $size= $requestData['size']?:10;
//        $checkRes = DataModelExample::checkField(
//            [
//
//                'product_id' => [
//                    'not_empty' => 1,
//                    'field_name' => 'product_id',
//                    'err_msg' => '参数缺失',
//                ],
//                'insured' => [
//                    'not_empty' => 1,
//                    'field_name' => 'insured',
//                    'err_msg' => '参数缺失',
//                ]
//            ],
//            $requestData
//        );
//        if(
//            !$checkRes['res']
//        ){
//            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
//        }

        $res =  CarInsuranceInstallment::findOneByUserId($this->loginUserinfo['id']);
        if($res){
            $res = $res->toArray();
        }
        else{
            $res = [];
        }
        if(
            empty($res)
        ){
            return $this->writeJson(
                200,
                [
                    'page' => $page,
                    'pageSize' => $size,
                    'total' => 0,
                    'totalPage' => 1 ,
                ],
                [
                    'companyInfo' => [

                    ],
                    'essentialFinanceInfo' => [],
                    'mapedByDateNumsRes' => [],
                    'mapedByDateAmountRes' => [],
                    'topSupplier' => [],
                    'topCustomer' => [],
                    'matchedRes' => [],
                    //'jinXiaoXiangFaPiaoRes' => $jinXiaoXiangFaPiaoRes,
                ]
            );
        }

        $companyRes = (new XinDongService())->getEsBasicInfoV3($res['ent_name']);

        //税务信息(今年)
        $essentialRes = (new GuoPiaoService())->getEssential($res['social_credit_code']);
        $mapedEssentialRes = [
            "owingType" => $essentialRes['data']['owingType'],
            "payTaxes" => $essentialRes['data']['payTaxes'],
            "regulations" => $essentialRes['data']['regulations'],
            "nature" => $essentialRes['data']['nature'],
            "creditPoint" => $essentialRes['data']['essential'][0]['creditPoint'],
            "creditLevel" => $essentialRes['data']['essential'][0]['creditLevel'],
            "year" => $essentialRes['data']['essential'][0]['year'],
            "taxpayerId" => $essentialRes['data']['essential'][0]['taxpayerId'],
        ];
        //近两年发票开票金额 需要分页拉取后计算结果
        // ['01', '08', '03', '04', '10', '11', '14', '15'] 分开拉取全部
        // $startDate 往前推一个月  推两年
        //纳税数据取得是两年的数据 取下开始结束时间
        $lastMonth = date("Y-m-01",strtotime("-1 month"));
        //两年前的开始月
        $last2YearStart = date("Y-m-d",strtotime("-2 years",strtotime($lastMonth)));
        //进销项发票信息 信动专用
        $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
            $res['social_credit_code'],
            $last2YearStart,
            $lastMonth
        );

        //按日期格式化
        $year1 = date('Y',strtotime("-2 years"));
        $year2 = date('Y',strtotime("-1 years"));
        $mapedByDateAmountRes = [
            '01' => [],
            '02' => [],
            '03' => [],
            '04' => [],
            '05' => [],
            '06' => [],
            '07' => [],
            '08' => [],
            '09' => [],
            '10' => [],
            '11' => [],
            '12' => [],
        ];
        $mapedByDateNumsRes = [
            '01' => [],
            '02' => [],
            '03' => [],
            '04' => [],
            '05' => [],
            '06' => [],
            '07' => [],
            '08' => [],
            '09' => [],
            '10' => [],
            '11' => [],
            '12' => [],
        ];
        foreach ($allInvoiceDatas as $InvoiceData){
            $month = date('m',strtotime($InvoiceData['billingDate']));
            $year = date('Y',strtotime($InvoiceData['billingDate']));
            $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
            $mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
            $mapedByDateNumsRes[$month][$year] ++;
        }

        //进销项发票信息 信动专用
        $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
            $res['social_credit_code'],
            $last2YearStart,
            $lastMonth
        );
        foreach ($allInvoiceDatas as $InvoiceData){
            $month = date('m',strtotime($InvoiceData['billingDate']));
            $year = date('Y',strtotime($InvoiceData['billingDate']));
            $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
            $mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
            $mapedByDateNumsRes[$month][$year] ++;
        }
        //销项
        $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
            $res['social_credit_code'],
            $last2YearStart,
            $lastMonth,
            2
        );
        foreach ($allInvoiceDatas as $InvoiceData){
            $month = date('m',strtotime($InvoiceData['billingDate']));
            $year = date('Y',strtotime($InvoiceData['billingDate']));
            $mapedByDateAmountRes[$month][$year] += $InvoiceData['totalAmount'];
            $mapedByDateAmountRes[$month][$year] = number_format($mapedByDateAmountRes[$month][$year],2);
            $mapedByDateNumsRes[$month][$year] ++;
        }
        //十大供应商
        //进销项发票信息 信动专用
        $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
            $res['social_credit_code'],
            $last2YearStart,
            $lastMonth
        );
        $supplier = [];
        foreach ($allInvoiceDatas as $InvoiceData){
            $supplier[$InvoiceData['salesTaxName']]['entName'] = $InvoiceData['salesTaxName'] ;
            $supplier[$InvoiceData['salesTaxName']]['totalAmount'] += $InvoiceData['totalAmount'] ;
        }
        //按时间倒叙排列
        usort($supplier, function($a, $b) {
            return $b['totalAmount'] <=> $a['totalAmount'];
        });
        $newSupplier = $sliced_array = array_slice($supplier, 0, 10);


        //十大客户
        //销项项发票信息 信动专用
        $allInvoiceDatas = CarInsuranceInstallment::getYieldInvoiceMainData(
            $res['social_credit_code'],
            $last2YearStart,
            $lastMonth,
            2
        );
        $customers = [];
        foreach ($allInvoiceDatas as $InvoiceData){
            $customers[$InvoiceData['purchaserName']]['entName'] = $InvoiceData['purchaserName'] ;
            $customers[$InvoiceData['purchaserName']]['totalAmount'] += $InvoiceData['totalAmount'] ;
        }
        //按时间倒叙排列
        usort($customers, function($a, $b) {
            return $b['totalAmount'] <=> $a['totalAmount'];
        });
        $newCustomers = $sliced_array = array_slice($customers, 0, 10);


        //匹配结果
        $matchedRes = CarInsuranceInstallmentMatchedRes::findAllByCondition(
            [
               'car_insurance_id'=>$res['id']
            ]
        );
        foreach ($matchedRes as &$matchedResItem){
            $matchedResItem['status_cname'] =  CarInsuranceInstallmentMatchedRes::getStatusMap()[$matchedResItem['status']];
            $matchedResItem['msg_arr'] =  $matchedResItem['msg']? json_decode($matchedResItem['msg'],true):[];
        }
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' => $size,
                'total' => $res['total'],
                'totalPage' => ceil($res['total']/$size) ,
            ],
            [
               'companyInfo' => [
                   'ENTNAME' => $companyRes['ENTNAME'],
                   'NAME' => $companyRes['NAME'],
                   'ESDATE' => $companyRes['ESDATE'],
                   'REGCAP' => $companyRes['REGCAP'],
                   'UNISCID' => $companyRes['UNISCID'],
                   'DOM' => $companyRes['DOM'],
                   'OPSCOPE' => $companyRes['OPSCOPE'],
               ],
               'essentialFinanceInfo' => $mapedEssentialRes,
               'mapedByDateNumsRes' => $mapedByDateNumsRes,
               'mapedByDateAmountRes' => $mapedByDateAmountRes,
               'topSupplier' => $newSupplier,
               'topCustomer' => $newCustomers,
               'matchedRes' => $matchedRes,
               //'jinXiaoXiangFaPiaoRes' => $jinXiaoXiangFaPiaoRes,
            ]
        );
    }


    /*
     * 查看详情
     *
     * */
    function baoYaConsultResult(): bool
    {
        $requestData =  $this->getRequestData();

        $checkRes = DataModelExample::checkField(
            [

                'id' => [
                    'not_empty' => 1,
                    'field_name' => 'id',
                    'err_msg' => '参数缺失',
                ],
//                'insured' => [
//                    'not_empty' => 1,
//                    'field_name' => 'insured',
//                    'err_msg' => '参数缺失',
//                ]
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }

        $res =   InsuranceData::findById($requestData['id']);
        $res = $res->toArray();
        //暂时去取最新的一个
        $resNew = MailReceipt::findByInsuranceId($res['id']);
        return $this->writeJson(
            200,
            [],
            $resNew?end($resNew):[]
        );
    }
}