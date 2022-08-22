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
            OnlineGoodsUser::getDailySmsNumsV2($phone) >= 15
        ){
            return $this->writeJson(201, null, [],  '请勿重复提交');
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