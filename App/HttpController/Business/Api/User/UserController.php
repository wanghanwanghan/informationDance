<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\Charge;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\Api\Wallet;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class UserController extends UserBase
{
    private $connDB;

    function onRequest(?string $action): ?bool
    {
        parent::onRequest($action);

        //DB链接名称
        $this->connDB = CreateConf::getInstance()->getConf('env.mysqlDatabase');

        return true;
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function reg()
    {
        $company=$this->request()->getRequestParam('company') ?? '';
        $username=$this->request()->getRequestParam('username') ?? '';
        $phone=$this->request()->getRequestParam('phone') ?? '';
        $email=$this->request()->getRequestParam('email') ?? '';

        $password=$this->request()->getRequestParam('password') ?? control::randNum(6);
        $avatar=$this->request()->getRequestParam('avatar') ?? '';

        $vCode=$this->request()->getRequestParam('vCode') ?? '';

        if (empty($phone) || empty($vCode)) return $this->writeJson(201,null,null,'手机号或验证码不能是空');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $vCodeInRedis = $redis->get($phone.'reg');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201,null,null,'验证码错误');

        try
        {
            $res=User::create()->where('phone',$phone)->get();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        //已经注册过了
        if ($res) return $this->writeJson(201,null,null,'手机号已经注册过了');

        try
        {
            $token=UserService::getInstance()->createAccessToken($phone,$password);

            $insert=[
                'username'=>$username,
                'password'=>$password,
                'phone'=>$phone,
                'email'=>$email,
                'avatar'=>$avatar,
                'token'=>$token,
                'company'=>$company
            ];

            User::create()->data($insert,false)->save();

            Wallet::create()->data(['phone'=>$phone],false)->save();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        return $this->writeJson(200,null,$insert,'注册成功');
    }

    function login()
    {
        $phone=$this->request()->getRequestParam('phone') ?? '';
        $vCode=$this->request()->getRequestParam('vCode') ?? '';

        if (empty($phone) || empty($vCode)) return $this->writeJson(201,null,null,'手机号或验证码不能是空');

        $redis = Redis::defer('redis');

        $redis->select(14);

        $vCodeInRedis = $redis->get($phone.'login');

        if ((int)$vCodeInRedis !== (int)$vCode) return $this->writeJson(201,null,null,'验证码错误');

        try
        {
            $userInfo=User::create()->where('phone',$phone)->get();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        if (empty($userInfo)) return $this->writeJson(201,null,null,'手机号不存在');

        $newToken=UserService::getInstance()->createAccessToken($userInfo->phone,$userInfo->password);

        try
        {
            $user=User::create()->get($userInfo->id);

            $user->update(['token'=>$newToken]);

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        $userInfo->newToken=$newToken;

        return $this->writeJson(200,null,$userInfo,'登录成功');
    }




}