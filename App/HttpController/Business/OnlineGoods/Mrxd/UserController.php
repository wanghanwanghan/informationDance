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
use App\HttpController\Models\MRXD\OnlineGoodsUser;
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

    function loginExample(): bool
    {
        $phone = $this->request()->getRequestParam('phone') ?? '';
        $vCode = $this->request()->getRequestParam('vCode') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';

        if (empty($phone) || (empty($vCode) && empty($password)))
            return $this->writeJson(201, null, null, '手机号或密码或验证码不能是空');

        try {
            $userInfo = User::create()->where('phone', $phone)->get();
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        if (empty($userInfo)) return $this->writeJson(201, null, null, '手机号不存在');

        if ($userInfo->getAttr('isDestroy') == 1) return $this->writeJson(201, null, null, '手机号已注销');

        $redis = Redis::defer('redis');
        $redis->select(14);

        $index = $phone . '_login_key';
        $loginNum = $redis->get($index);

        if (!empty($loginNum) && $loginNum - 0 >= 5) {
            return $this->writeJson(201, null, null, '已登录失败5次,1小时内禁止登录');
        }

        //密码或者验证码登录
        if (!empty($vCode)) {
            $vCodeInRedis = $redis->get($phone . 'login');
            if (!is_numeric($vCodeInRedis) || $vCodeInRedis <= 1000) {
                $vCodeInRedis = $redis->get($phone . 'reg');
            }
            if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201, null, null, '验证码错误');
        } elseif (!empty($password)) {
            $password = trim($password);
            $mysql_pwd = trim($userInfo->getAttr('password'));
            empty($loginNum) ? $redis->set($index, 1, 3600) : $redis->incr($index);
            if ($password !== $mysql_pwd) return $this->writeJson(201, null, null, '密码错误');
        } else {
            //连续输入错误 5 次，禁止登录 1 小时
            empty($loginNum) ? $redis->set($index, 1, 3600) : $redis->incr($index);
            return $this->writeJson(201, null, null, '登录失败');
        }

        $newToken = UserService::getInstance()->createAccessToken(
            $userInfo->getAttr('phone'), $userInfo->getAttr('password')
        );

        try {
            User::create()->get($userInfo->getAttr('id'))->update(['token' => $newToken]);
        } catch (\Throwable $e) {
            return $this->writeErr($e, __FUNCTION__);
        }

        $userInfo->setAttr('newToken', $newToken);

        return $this->writeJson(200, null, $userInfo, '登录成功');
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

        return $this->writeJson(
            200,
            [ ] ,
            [
                1=>'宝花',
                2=>'宝树',
                3=>'小花',
                4=>'小树',
            ],
            '成功',
            true,
            []
        );
    }

    function getBasicInfo(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

        return $this->writeJson(
            200,
            [ ] ,
            [
                'id' => 1,
                'user_name' => '田大脚',
                'money' => 1000,
            ],
            '成功',
            true,
            []
        );
    }

    function applyForWithdrawal(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;

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

        $id = OnlineGoodsUser::addRecordV2(
            [
                'source' => OnlineGoodsUser::$source_self_register,
                'user_name' => $phone,
                'phone' => $phone,
                'password' => '',
                'email' => '',
                'money' => '',
                'token' => '',
            ]
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'OnlineGoodsUser $id' => $id,
                'OnlineGoodsUser $phone' => $phone,
            ])
        );
        $newToken = UserService::getInstance()->createAccessToken(
            $phone,
            $phone
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'OnlineGoodsUser $newToken' => $newToken
            ])
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

    function register(): bool
    {
        $requestData =  $this->getRequestData();
        $phone = $requestData['phone'] ;
        $code = $requestData['code'] ;
        if(
            OnlineGoodsUser::getRandomDigit($phone)!= $code
        ){
            return $this->writeJson(201, null, [],  '验证码不正确或已过期');
        }

        $id = OnlineGoodsUser::addRecordV2(
            [
                'source' => OnlineGoodsUser::$source_self_register,
                'user_name' => $phone,
                'phone' => $phone,
                'password' => '',
                'email' => '',
                'money' => '',
                'token' => '',
            ]
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'OnlineGoodsUser $id' => $id,
                'OnlineGoodsUser $phone' => $phone,
            ])
        );

        //- 有邀请码的话 解析邀请码 设置邀请人
        $invitation_code = trim($requestData['invitation_code']);
        if(($invitation_code)){
            $invitatedBy = CommonService::decodeInvitationCodeToId($invitation_code);
            OnlineGoodsUserInviteRelation::addRecordV2(
                [
                    'user_id' => $this->loginUserinfo['id'],
                    'invite_by' => $invitatedBy,
                ]
            );
        }

        $newToken = UserService::getInstance()->createAccessToken(
            $phone,
            $phone
        );

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'OnlineGoodsUser $newToken' => $newToken
            ])
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
    function daikuanOrderLists(): bool
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
                'avatar'=>  '/static/img/aaa.jpg',
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
                'avatar'=>  '/static/img/aaa.jpg',
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
                //用户姓名
                'name'=>  '张三',
                //产品名称
                'product_name'=>  '产品A',
                //定金金额
                'order_money'=>  '1000',
                //收益金额
                'income_money'=>  '1000',
                //时间
                'income_date'=>  '2022-10-09',
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