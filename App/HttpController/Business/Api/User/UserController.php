<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\User;
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
        $this->connDB = \Yaconf::get('env.mysqlDatabase');

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

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        return $this->writeJson(200,null,$insert,'注册成功');
    }

    function login()
    {
        $phone=$this->request()->getRequestParam('phone') ?? '';
        $password=$this->request()->getRequestParam('password') ?? '123456';

        try
        {
            $userInfo=User::create()->where('phone',$phone)->where('password',$password)->get();

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        if (!$userInfo) return $this->writeJson(201,null,null,'登录信息错误');

        $newToken=UserService::getInstance()->createAccessToken($userInfo->phone,$userInfo->password);

        try
        {
            $user=User::create()->get($userInfo['id']);

            $user->update(['token'=>$newToken]);

        }catch (\Throwable $e)
        {
            return $this->writeErr($e,'orm');
        }

        $userInfo->newToken=$newToken;

        return $this->writeJson(200,null,$userInfo,'登录成功');
    }




}