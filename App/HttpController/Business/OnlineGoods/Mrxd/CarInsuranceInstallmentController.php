<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
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
        return $this->writeJson(
            200,[ ] ,
            //CommonService::ClearHtml($res['body']),
            $res['data'],
            '成功',
            true,
            []
        );
    }

    function baoYaConsultResultList(): bool
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
        $res = InsuranceData::getDataLists(
            [
                ['field'=>'user_id','value'=>$this->loginUserinfo['id'],'operate'=>'=']
            ],
            $page
        );

        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' => $size,
                'total' => $res['total'],
                'totalPage' => ceil($res['total']/$size) ,
            ],
            $res['data']
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