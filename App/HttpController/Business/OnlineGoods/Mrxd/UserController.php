<?php

namespace App\HttpController\Business\OnlineGoods\Mrxd;

use App\ElasticSearch\Service\ElasticSearchService;
use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DataModelExample;
use App\HttpController\Models\AdminV2\DeliverDetailsHistory;
use App\HttpController\Models\AdminV2\DeliverHistory;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\AdminV2\InsuranceData;
use App\HttpController\Models\AdminV2\MailReceipt;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\MRXD\OnlineGoodsCommissions;
use App\HttpController\Models\MRXD\OnlineGoodsDaikuanProducts;
use App\HttpController\Models\MRXD\OnlineGoodsTiXianJiLu;
use App\HttpController\Models\MRXD\OnlineGoodsUser;
use App\HttpController\Models\MRXD\OnlineGoodsUserBaoXianOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserDaikuanOrder;
use App\HttpController\Models\MRXD\OnlineGoodsUserInviteRelation;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Models\RDS3\CompanyInvestor;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\Sms\SmsService;
use App\HttpController\Service\User\UserService;
use App\HttpController\Service\XinDong\XinDongService;

class UserController extends \App\HttpController\Business\OnlineGoods\Mrxd\ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function sendSms(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;

        if (empty($phone) ){
            return $this->writeJson(201, null, null, '手机号不能是空');
        }

        //重复提交校验
        if(
            !ConfigInfo::setRedisNx('online_sendSms',3)
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
        }

        //记录今天发了多少次
        OnlineGoodsUser::addDailySmsNumsV2($phone);

        //每日发送次数限制
        if(
            OnlineGoodsUser::getDailySmsNumsV2($phone) >= 20
        ){
            return $this->writeJson(201, null, [],  '今天已发送'.OnlineGoodsUser::getDailySmsNumsV2($phone).'次，超出每天最多发送次数');
        }

        $digit = OnlineGoodsUser::createRandomDigit();

       //发短信
        if(
            CommonService::IsProductionEnv()
        ){
            $res = (new AliSms())->sendByTempleteV2($phone, 'SMS_218160347',[
                'code' => $digit,
            ]);
        }
        else{
            $res = true;
        }

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
        OnlineGoodsUser::setRandomDigit($phone,$digit);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'setRandomDigit' => [
                    'getRandomDigit'=>OnlineGoodsUser::getRandomDigit($phone),
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

    function zhiJinUserLists(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $res = OnlineGoodsUser::findAllByCondition([]);
        $returnDatas = [];
        foreach ($res as $value){
            $returnDatas[$value['id']] = $value['user_name'];
        }
        return $this->writeJson(
            200,
            [ ] ,
            $returnDatas,
            '成功',
            true,
            []
        );
    }

    function getBasicInfo(): bool
    {
        $requestData =  $this->getRequestData();
        $userInfo = $this->loginUserinfo;



        return $this->writeJson(
            200,
            [ ] ,
            [
                'id' => 1,
                'user_name' => $userInfo['user_name'],
                'total_income' => 0,
                'total_commission' => 0,
                'total_withdraw' => 0,
                'invite_code' =>  CommonService::encodeIdToInvitationCode($userInfo['id']),
                'money' => $userInfo['money'],
                'avatar' => 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
            ],
            '成功',
            true,
            []
        );
    }


    //申请提现
    function applyForWithdrawal(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        $userInfo = $this->loginUserinfo;
        $uid = $userInfo['id'];
        $uid = 1 ;
        OnlineGoodsTiXianJiLu::addRecordV2([
            'user_id' => $uid,
            'amount' => $requestData['money'],
            'remark' => $requestData['remark']?:'',
            'audit_state' => OnlineGoodsTiXianJiLu::$audit_state_init,
            'audit_details' => '',
            'pay_state' => OnlineGoodsTiXianJiLu::$pay_state_init,
            'pay_details' => '',
            'da_kuan_type' => OnlineGoodsTiXianJiLu::$pay_type_bank,
            'kai_hu_hang' => $requestData['kai_hu_hang'],
            'kai_hu_ming' => $requestData['kai_hu_ming'],
            'yin_hang_ka_hao' => $requestData['yin_hang_ka_hao'],
        ]);
        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '已提交',
            true,
            []
        );
    }

    function ZhiJinAccountFlow(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [
                [
                    'id'=>1,
                    'money'=>1000,
                    'details' => '你邀请的张老三下单了，您得到一笔佣金',
                    'user_id' => 1,
                    'type' => 5,
                    'type_cname' => '佣金',
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    'created_at'=>1665367946,
                ],
                [
                    'id'=>1,
                    'money'=>100,
                    'details' => '',
                    'user_id' => 1,
                    'type' => 5,
                    'type_cname' => '提现',
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    'created_at'=>1665367946,
                ],
            ],
            '成功',
            true,
            []
        );
    }


    function applyWithdrawalRecords(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [
                [
                    'id'=>1,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ],
                [
                    'id'=>2,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ],
                [
                    'id'=>3,
                    'money'=>1000,
                    'state_cname' => '审核中',
                    'pay_state_cname' => '待打款',
                    'user_id' => 1,
                    'user_name' =>  '李循环',
                    'user_money' =>  100,
                    // 详情
                    'details' => '',
                    'created_at'=>1665367946,
                    'attaches'=>[],
                    'pass_date'=> '2022-09-10 10:00:00',
                    'pay_date'=> '2022-09-10 10:00:00',
                ]
            ],
            '成功',
            true,
            []
        );
    }

    function setCommisionRate(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }
    function setBaoXianCommisionRate(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $userInfo = $this->loginUserinfo;
        $userInfo['id'] = 1;

        if(
            $requestData['real']
        ){
            $commissionInfo = OnlineGoodsCommissions::findOneByCondition([
                'id' => $requestData['id'],
                'commission_owner' => $userInfo['id'],
                'state' => OnlineGoodsCommissions::$commission_state_init
            ]);


            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'id' => $requestData['id'],
                        'commission_owner' => $userInfo['id'],
                        'state' => OnlineGoodsCommissions::$commission_state_init
                    ]
                ])
            );


            if(empty($commissionInfo)){
                return $this->writeJson(
                    203,
                    [ ] ,
                    [

                    ],
                    '没权限设置该订单',
                    true,
                    []
                );
            }

            //改为已设置成功
            OnlineGoodsCommissions::updateById($commissionInfo->id,
                [
                    'state' => OnlineGoodsCommissions::$commission_state_seted
                ]
            );

            //发放 金额
            $OrderInfo = OnlineGoodsUserBaoXianOrder::findById($commissionInfo->commission_order_id);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    [
                        'setBaoXianCommisionRate_amount' =>  $OrderInfo->amount
                    ]
                ])
            );
            OnlineGoodsCommissions::grantByItem($commissionInfo->toArray(),$OrderInfo->amount) ;
        }

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }
    function setDaiKuanCommisionRate(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        $userInfo = $this->loginUserinfo;
        $userInfo['id'] = 1;
        //校验权限：校验设置人
        $commissionInfo = OnlineGoodsCommissions::findOneByCondition([
            'id' => $requestData['id'],
            'commission_owner' => $userInfo['id'],
            'state' => OnlineGoodsCommissions::$commission_state_init
        ]);

        //todo：校验  rate 不能超出信动给的rate

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'id' => $requestData['id'],
                    'commission_owner' => $userInfo['id'],
                    'state' => OnlineGoodsCommissions::$commission_state_init
                ]
            ])
        );


        if(empty($commissionInfo)){
            return $this->writeJson(
                203,
                [ ] ,
                [

                ],
                '没权限设置该订单',
                true,
                []
            );
        }

        //改为已设置成功
        OnlineGoodsCommissions::updateById($commissionInfo->id,
            [
                'state' => OnlineGoodsCommissions::$commission_state_seted,
                'comission_rate' => $requestData['rate'],
            ]
        );

        //发放 金额
        $OrderInfo = OnlineGoodsUserDaikuanOrder::findById($commissionInfo->commission_order_id);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                [
                    'setDaiKuanCommisionRate_amount' =>  $OrderInfo->amount
                ]
            ])
        );
        OnlineGoodsCommissions::grantByItem($commissionInfo->toArray(),$OrderInfo->amount) ; 

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }

    //粉丝基本信息
    function fansBasicInfo(): bool
    {
        $requestData =  $this->getRequestData();
        $fans_id = $requestData['fans_id'] ;
        //fans_id
        //先看是否是粉丝
//        if(
//         !OnlineGoodsUserInviteRelation::IsFans($fans_id,$this->loginUserinfo['id'])
//        ){
//            return $this->writeJson(
//                200,
//                [ ] ,
//                [
//
//                ],
//                '没权限',
//                true,
//                []
//            );
//        }

        $userInfo = OnlineGoodsUser::findById($fans_id);
        $invitorUserInfo = OnlineGoodsUserInviteRelation::findByUser($fans_id);
        if($invitorUserInfo){
            $invitorUserInfo = OnlineGoodsUser::findById($invitorUserInfo->invite_by);
        }
        return $this->writeJson(
            200,
            [ ] ,
            [
                'name'=>$userInfo->user_name,
                'zhi_jin_account'=>$userInfo->phone,
                'commission_order_nums'=>'',
                'invitor'=> $invitorUserInfo?$invitorUserInfo->user_name:'',
                'invitor_mobile'=> $invitorUserInfo?$invitorUserInfo->phone:'',
            ],
            '成功',
            true,
            []
        );
    }
    //
    function shareIncome(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [

            ],
            '成功',
            true,
            []
        );
    }

    function login(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        if(
            OnlineGoodsUser::getRandomDigit($phone)!= $code
        ){
            return $this->writeJson(201, null, [],  '验证码不正确或已过期');
        }

        $userInfo = OnlineGoodsUser::findByPhone($phone);
        if(
            !$userInfo
        ){
            return $this->writeJson(201, null, [],  '请先注册');
        }

        $newToken = UserService::getInstance()->createAccessToken(
            $phone,
            $phone
        );

        $res = OnlineGoodsUser::findByPhone($phone);
        $res = $res->toArray();
        if($res['token']){
            return $this->writeJson(
                200,[ ] ,$res['token'],
                '成功',
                true,
                []
            );
        }

        OnlineGoodsUser::updateById(
            $userInfo->id,
            [
                'token'=>$newToken
            ]
        );
        return $this->writeJson(
            200,[ ] ,$newToken,
            '成功',
            true,
            []
        );
    }

    //register
    function register(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        if(
            OnlineGoodsUser::getRandomDigit($phone)!= $code
        ){
            //测试环境不发
            if(
                CommonService::IsProductionEnv()
            ){
                return $this->writeJson(201, null, [],  '验证码不正确或已过期');
            }

        }

        if(
            OnlineGoodsUser::findByPhone($phone)
        ){
            return $this->writeJson(201, null, [],  '该手机已经被注册');
        }

        $id = OnlineGoodsUser::addRecordV2(
            [
                'source' => OnlineGoodsUser::$source_self_register,
                'user_name' => $requestData['name'],
                'phone' => $phone,
                'password' => '',
                'email' => '',
                'money' => '',
                'token' => '',
            ]
        );

        //- 有邀请码的话 解析邀请码 设置邀请人
        $invitation_code = trim($requestData['invitation_code']);
        if(($invitation_code)){
            $invitatedBy = CommonService::decodeInvitationCodeToId($invitation_code);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'registerZhiJin $uid' => $id,
                    'registerZhiJin $phone' => $phone,
                    'registerZhiJin $invitation_code' => $invitation_code,
                    'registerZhiJin $invitatedBy' => $invitatedBy,
                ])
            );
            $res1 = OnlineGoodsUserInviteRelation::addRecordV2(
                [
                    'user_id' => $id,
                    'invite_by' => $invitatedBy,
                ]
            );
            if(   !$res1   ){
                return $this->writeJson(201, null, [],  '系统错误！请联系管理员');
            }

        }

        $newToken = UserService::getInstance()->createAccessToken(
            $phone,
            $phone
        );
 
        $res = OnlineGoodsUser::findByPhone($phone);
        $res = $res->toArray();
        if($res['token']){
            return $this->writeJson(
                200,[ ] ,$res['token'],
                '成功',
                true,
                []
            );
        }

        OnlineGoodsUser::updateById(
            $id,
            [
                'token'=>$newToken
            ]
        );
        return $this->writeJson(
            200,[ ] ,$newToken,
            '成功',
            true,
            []
        );
    }


    function getInvitationCode(): bool
    {
        $requestData =  $this->getRequestData();

        $userInfo = $this->loginUserinfo;
        $code = CommonService::encodeIdToInvitationCode($userInfo['id']);
        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                    'invitationCode'=>$code,
                ]
            ]
        );
        return $this->writeJson(
            200,
            [ ] ,
            $code
            ,
            '成功',
            true,
            []
        );
    }

    function loanOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
            [
                'id'=>2,
                //产品名称
                'product_name'=>'帅哥贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张大锤',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }
    function baoxianOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;
        $uid = $userInfo['id'];
        $uid = 11;

        if($requestData['real']){
            $commissions = OnlineGoodsCommissions::findAllByCondition([
                //分佣的用户
                'user_id' => $requestData['fans_id'],
                'commission_owner' => $uid,
                'state' => OnlineGoodsCommissions::$commission_state_init,
            ]);
            CommonService::writeTestLog(
                [
                    'baoxianOrderLists findAllByCondition'=>[
                        'user_id' => $requestData['fans_id'],
                        'commission_owner' => $uid,
                        'state' => OnlineGoodsCommissions::$commission_state_init,
                    ]
                ]
            );
            return $this->writeJson(
                200,
                [
                    'page' => $page,
                    'pageSize' =>$pageSize,
                    'total' => $total,
                    'totalPage' => ceil( $total/ $pageSize ),
                ] ,
                $commissions
                ,
                '成功',
                true,
                []
            );
        }

        $exampleDatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
            [
                'id'=>2,
                //产品名称
                'product_name'=>'帅哥贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张大锤',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }

    // 贷款分佣订单
    function daikuanOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;
        $fansUser = OnlineGoodsUser::findById($requestData['fans_id']);

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        //贷款订单
        $conditions = [
            'zhijin_phone' => $fansUser->phone,
        ];
        if($requestData['commission_set_state']){
            $conditions[] =  $requestData['commission_set_state'];
        }

        $daiKuanOrders = OnlineGoodsUserDaikuanOrder::findByConditionWithCountInfo(
            $conditions,$page,$pageSize
        );

        foreach ($daiKuanOrders['data'] as &$dataItem){
            $tmpProduct  = OnlineGoodsDaikuanProducts::findById($dataItem['product_id']);
            $dataItem['product_name'] = $tmpProduct->name;
        }
        //product_name


        $total = $daiKuanOrders['total'] ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $daiKuanOrders['data']
            ,
            '成功',
            true,
            []
        );
    }
    function ZhiJinOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //用户姓名
                'name'=>  '张三',
                //邀请人姓名
                'inviter'=>  '张大三',
                //订单数量
                'order_nums'=>  '100',
                //累计收益
                'total_income'=>  '1000',
                //粉丝数量
                'total_fan_nums'=>  '1000',

                //头像
                'avatar'=>  'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                //加入时间
                'join_at'=>'2022-10-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }
    function ZhiJinFansOrderLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;
        $useId = $userInfo['id'];
        $returnDatas = [];

        //如果是VIP 可以设置全部粉丝
        if(
            OnlineGoodsUser::IsVipV2($useId)
        ){
            $returnDatas = OnlineGoodsUserInviteRelation::getVipsAllInvitedUser($useId);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'ZhiJinFansOrderLists'=>[
                        'uid'=>$useId,
                        'IsVipV2'=>true,
                        '$returnDatas'=>$returnDatas,
                    ],
                ])
            );
        }
        else{
            $returnDatas = OnlineGoodsUserInviteRelation::getAllInvitedUser($useId);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'ZhiJinFansOrderLists'=>[
                        'uid'=>$useId,
                        'IsVipV2'=>false,
                        '$returnDatas'=>$returnDatas,
                    ],
                ])
            );
        }

        //XXXXX
        foreach ($returnDatas as &$valueData){
            $userInfo = OnlineGoodsUser::findById($valueData['user_id']);
            $valueData['name'] = $userInfo->user_name;
            $valueData['mobile'] = $userInfo->phone;
            $valueData['total_fan_nums'] =  '' ;
            $valueData['order_nums'] =  '' ;
            $valueData['join_at'] = date('Y-m-d H:i:s',$userInfo->created_at);
            $valueData['avatar'] = 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png' ;
        }
        $total = count($returnDatas);
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $returnDatas
            ,
            '成功',
            true,
            []
        );
    }

    //置金粉丝列表
    function ZhiJinFansLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;
        $userInfo = $this->loginUserinfo;

        //$isVip = OnlineGoodsUser::IsVipV2($userInfo['id']);
        $isVip = OnlineGoodsUser::IsVipV2(1);
        $inviters = OnlineGoodsUserInviteRelation::getVipsAllInvitedUser(1);
        foreach ($inviters as $inviterData){
            $tmpUserInfo = OnlineGoodsUser::findById($inviterData['user_id']);
            $tmpUserInfo = $tmpUserInfo->toArray();
            $inviterData['user_commission_amount'] = 1000 ;
            //$inviterData['user_avatar'] = $tmpUserInfo['avatar'] ;
            $inviterData['user_avatar'] = 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png' ;
            $inviterData['user_name'] = $tmpUserInfo['user_name'] ;
            $inviterData['user_join_time'] = date('Y-m-d H:i:s',$tmpUserInfo['created_at']) ;

            $tmpUserInvoterInfo = OnlineGoodsUser::findById($inviterData['invite_by']);
            $tmpUserInvoterInfo = $tmpUserInvoterInfo->toArray();
            $inviterData['invite_user_name'] = $tmpUserInvoterInfo['user_name'] ;
        }
        //找到所有的粉丝
        // vip 》粉丝》
        CommonService::writeTestLog(
            [
                'ZhiJinFansLists'=>[
                    $inviters
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //用户姓名
                'name'=>  '张三',
                //邀请人姓名
                'inviter'=>  '张大三',
                //订单数量
                'order_nums'=>  '100',
                //累计收益
                'total_income'=>  '1000',
                //粉丝数量
                'total_fan_nums'=>  '1000',

                //头像
                'avatar'=>  'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                //加入时间
                'join_at'=>'2022-10-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $inviters
            ,
            '成功',
            true,
            []
        );
    }

    function incomeLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                'avatar'=> 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                'purchaser_mobile'=>'132****6193',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //介绍人
                'introducer'=>'张大花',
                //介绍人所得分佣比例
                'introducer_commision'=>'50%',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                'commission'=>50,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ]
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }

    //前台-我的分佣列表
    function commissionLists(): bool
    {
        $requestData =  $this->getRequestData();
        $page =  $requestData['page']?:1;
        $pageSize =  $requestData['pageSize']?:100;

        $userInfo = $this->loginUserinfo;

        CommonService::writeTestLog(
            [
                'getInvitationCode'=>[
                    '$userInfo'=>[
                        'id'=>$userInfo['id'],
                        'user_name'=>$userInfo['user_name'],
                        'phone'=>$userInfo['phone'],
                    ],
                ]
            ]
        );

        $exampleDatas = [
            [
                'id'=>1,
                //产品名称
                'product_name'=>'美人贷',
                'avatar'=> 'http://api.test.meirixindong.com/Static/OtherFile/default_avater.png',
                'purchaser_mobile'=>'132****6193',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //介绍人
                'introducer'=>'张大花',
                //介绍人所得分佣比例
                'introducer_commision'=>'50%',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //所得佣金
                'commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
            [
                'id' => 2,
                //产品名称
                'product_name' => '美人贷',
                //产品id
                'product_id'=>1,
                //购买人
                'purchaser'=>'张小花',
                //介绍人
                'introducer'=>'张大花',
                //介绍人所得分佣比例
                'introducer_commision'=>'50%',
                //订单金额
                'price'=>10000,
                //信动所得佣金 - 佣金表
                'xindong_commission'=>500,
                //设置分佣状态
                'commission_set_state_cname'=>'已设置分佣',
                //分佣状态
                'commission_state_cname'=>'已领取分佣',
                //下单时间
                'order_time'=>'2022-09-09',
                'created_at'=>1665367946,
                'state'=>1,
                'state_cname'=> '已成交',
            ],
        ];
        $total = 100 ;
        return $this->writeJson(
            200,
            [
                'page' => $page,
                'pageSize' =>$pageSize,
                'total' => $total,
                'totalPage' => ceil( $total/ $pageSize ),
            ] ,
            $exampleDatas
            ,
            '成功',
            true,
            []
        );
    }



    function OnlineSignOut(): bool
    {
        $requestData =  $this->getRequestData();
        OnlineGoodsUser::updateById(
            $this->loginUserinfo['id'],
            [
                'token' => '',
            ]
        );

        return $this->writeJson(
            200,[ ] ,[],
            '成功',
            true,
            []
        );
    }

    function OnlineLogOut(): bool
    {
        $requestData =  $this->getRequestData();
        $rand = rand(100,999);
        if(
            OnlineGoodsUser::findByPhone(
                'del_'.$this->loginUserinfo['phone']
            )
        ){
            return $this->writeJson(
                201,[ ] ,[],
                '请重试',
                true,
                []
            );
        }

        OnlineGoodsUser::updateById(
            $this->loginUserinfo['id'],
            [
                'token' => '',
                'phone' => 'del_'.$this->loginUserinfo['phone'].'_'.$rand,
            ]
        );


    }

}