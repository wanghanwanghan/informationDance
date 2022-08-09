<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\carInsuranceInstallment;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
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
        $callback = $this->getRequestData('callback', 'https://pc.meirixindong.com/');

        $orderNo = control::getUuid(20);
        $res_raw = (new GuoPiaoService())->getAuthentication($this->getRequestData('getAuthentication1'), $callback, $orderNo);

        $res = jsonDecode($res_raw);

        carInsuranceInstallment::addRecordV2(
            [
                'user_id' => $this->loginUserinfo['id'],
                'product_id' => $this->loginUserinfo['id']?:0,
                'ent_name' => $requestData['ent_name'],
                'legal_phone' => $requestData['legal_phone'],
                'legal_person' => $requestData['legal_person'],
                'legal_person_id_card' => $requestData['legal_person_id_card'],
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