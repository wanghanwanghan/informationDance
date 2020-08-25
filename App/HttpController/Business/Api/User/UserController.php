<?php

namespace App\HttpController\Business\Api\User;

use App\HttpController\Models\Api\User;
use App\HttpController\Service\User\UserService;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;
use EasySwoole\RedisPool\Redis;

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

        $password=$this->request()->getRequestParam('password') ?? 123456;
        $avatar=$this->request()->getRequestParam('avatar') ?? '';

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






    function wanghan()
    {
        $sql=DDLBuilder::table('information_dance_user',function (Table $table)
        {
            $table->setTableComment('')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);

            $table->colInt('id',11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('username',20)->setDefaultValue('');
            $table->colVarChar('password',20)->setDefaultValue('');
            $table->colVarChar('phone',20)->setDefaultValue('');
            $table->colVarChar('email',100)->setDefaultValue('');
            $table->colInt('created_at',11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at',11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index','phone');
        });

        $obj=Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->recycleObj($obj);

        $this->writeJson(200,'ok','success');
    }
}