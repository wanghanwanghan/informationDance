<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\MRXD\InsuranceDataHuiZhong;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;


class HuiZhongController extends \App\HttpController\Business\OnlineGoods\Mrxd\ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function getProducts(): bool
    {
        $allProducts = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProducts();

        return $this->writeJson(
            200,[ ] ,$allProducts,
            '成功',
            true,
            []
        );
    }

    function getProductDetail(): bool
    {
        if($this->getRequestData('id')<=0){
            return $this->writeJson(
                200,[ ] ,[],
                '参数缺失',
                true,
                []
            );
        }
        return $this->writeJson(
            200,[ ] ,
            (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $this->getRequestData('id')
            ),
            '成功',
            true,
            []
        );
    }

    //咨询
    function preAuthorization(): bool
    {
        $requestData =  $this->getRequestData();
        $checkRes = DataModelExample::checkField(
            [

                'product_id' => [
                    'not_empty' => 1,
                    'field_name' => 'product_id',
                    'err_msg' => '参数缺失（产品）',
                ],
                'ent_name' => [
                    'not_empty' => 1,
                    'field_name' => 'ent_name',
                    'err_msg' => '参数缺失（企业）',
                ],
                'business_license_file' => [
                    'not_empty' => 1,
                    'field_name' => 'business_license_file',
                    'err_msg' => '参数缺失（营业执照）',
                ],
            ],
            $requestData
        );
        if(
            !$checkRes['res']
        ){
            return $this->writeJson(203,[ ] , [], $checkRes['msgs'], true, []);
        }


        //设置验证码
        $phone = $requestData['legal_person_phone'];
        $code = $requestData['code'];
        $redisCode = OnlineGoodsUser::getRandomDigit($phone,'huizhong_sms_code_');
        if(
            $redisCode!=$code
        ){
            return $this->writeJson(203,[ ] , [], '验证码错误', true, []);
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$code' => $code,
                '$redisCode' => $redisCode,
            ])
        );

        $res = InsuranceDataHuiZhong::addRecord(
            [
                'post_params' => json_encode(
                    $requestData
                ),
                'product_id' => $requestData['product_id'],
                'ent_name' => $requestData['ent_name'], //
                'business_license_file' => $requestData['business_license_file'], //
                'id_card_back_file' => $requestData['id_card_back_file'], //
                'id_card_front_file' => $requestData['id_card_front_file'], //
                'public_account' => $requestData['public_account'], //
                'legal_person_phone' => $requestData['legal_person_phone'], //
                'business_license' => $requestData['business_license'], //
                'user_id' => $this->loginUserinfo['id']?:1,
                'status' =>  1,
            ]
        );

        return $this->writeJson(
            200,[ ] ,
             $res,
            '尊敬的用户！您的询价单已经提交，请在1到2个工作日内查看短信通知',
            true,
            []
        );
    }

//    public function uploadeFile(){
//        $requestData =  $this->getRequestData();
//        $files = $this->request()->getUploadedFiles();
//        $fileNames = [];
//        $succeedNums = 0;
//        foreach ($files as $key => $oneFile) {
//            try {
//                $fileName = $oneFile->getClientFilename();
//                $path = OTHER_FILE_PATH . $fileName;
////                if(file_exists($path)){
////                    return $this->writeJson(203, [], [],'文件已存在！');;
////                }
//
//                $res = $oneFile->moveTo($path);
//                if(!file_exists($path)){
//                    CommonService::getInstance()->log4PHP(
//                        json_encode(['uploadeCompanyLists   file_not_exists moveTo false ', 'params $path '=> $path,  ])
//                    );
//                    return $this->writeJson(203, [], [],'文件移动失败！');
//                }
//                $succeedNums ++;
//                $fileNames[] = '/Static/OtherFile/'.$fileName;
//            } catch (\Throwable $e) {
//                return $this->writeJson(202, [], $fileNames,'上传失败'.$e->getMessage());
//            }
//        }
//        return $this->writeJson(200, [], $fileNames,'上传成功 文件数量:'.$succeedNums);
//    }

    function huiZhongSendSms(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;

        if (empty($phone) ){
            return $this->writeJson(201, null, null, '手机号不能是空');
        }

        //重复提交校验
        if(
            !ConfigInfo::setRedisNx('huizhong_sendSms',3)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        //记录今天发了多少次
        OnlineGoodsUser::addDailySmsNumsV2($phone,'daily_huizhong_sendSms_');

        //每日发送次数限制
        $res = OnlineGoodsUser::getDailySmsNumsV2($phone,'daily_huizhong_sendSms_');
        if(
            $res >= 15
        ){
            return $this->writeJson(201, null, [],  '超出每日发送次数限制');
        }

        $digit = OnlineGoodsUser::createRandomDigit();

        //发短信
        $res = (new AliSms())->sendByTempleteV2($phone, 'SMS_218160347',[
            'code' => $digit,
        ]);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sendByTemplete' => [
                    'sendByTemplete'=>$res,
                    '$digit'=>$digit
                ],
            ])
        );
        if(!$res){
            return $this->writeJson(201, null, [],  '短信发送失败');
        }

        //设置验证码
        OnlineGoodsUser::setRandomDigit($phone,$digit,'huizhong_sms_code_');
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'setRandomDigit' => [
                    'getRandomDigit'=>OnlineGoodsUser::getRandomDigit($phone,'huizhong_sms_code_'),
                ],
            ])
        );

        return $this->writeJson(
            200,[ ] ,$res,
            '成功',
            true,
            []
        );
    }

    //咨询结果
    function consultHuiZhongResult(): bool
    {
        $requestData =  $this->getRequestData();
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
        $res =  MailReceipt::findById( $this->loginUserinfo['id']);
        $res = $res->toArray();
        return $this->writeJson(
            200,[ ] ,
            //CommonService::ClearHtml($res['body']),
            $res['body'],
            '成功',
            true,
            []
        );
    }
    function huiZhongConsultResultList(): bool
    {
        $requestData =  $this->getRequestData();
        $page = $requestData['page']?:1;
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
        $res =  InsuranceDataHuiZhong::gteLists(
            [
                [
                    'field'=>'user_id',
                    'value'=>$this->loginUserinfo['id']?:1,
                    'operate'=>'='
                ]
            ],$page
        );

        return $this->writeJson(
            200,[
            'page' => $page,
            'pageSize' => $size,
            'total' => $res['total'],
            'totalPage' => ceil($res['total']/$size) ,
            ] ,
            //CommonService::ClearHtml($res['body']),
            $res['data'],
            '成功',
            true,
            []
        );
    }
    /*
    * 查看详情
    *
    * */
    function huiZhongConsultResult(): bool
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

        $res =   InsuranceDataHuiZhong::findById($requestData['id']);
        $res = $res->toArray();
        //暂时去取最新的一个
        $resNew = MailReceipt::findByInsuranceHuiZhongId($res['id']);
        return $this->writeJson(
            200,
            [],
            $resNew?end($resNew):[]
        );
    }
}