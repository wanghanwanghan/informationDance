<?php

namespace App\HttpController\Business\AdminNew\Mrxd\User;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewUser;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;

class UserController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function reg(): bool
    {
        return $this->writeJson();
    }

    /**
     * 用户登录
     */
    public function userLogin()
    {
        $username = $this->request()->getRequestParam('username') ?? '';
        $password = $this->request()->getRequestParam('password') ?? '';
        if (empty($username) || empty($password) ) return $this->writeJson(201, null, null, '登录信息错误');
        $info = AdminNewUser::create()->where("username = '{$username}' and password = '{$password}'")->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '账号密码错误');
        }else{
            $newToken = UserService::getInstance()->createAccessToken($info->phone, $info->password);
            $info->update(['token' => $newToken]);
            $info->token = $newToken;
            return $this->writeJson(200, $info, null, '登录成功');
        }
    }

    /**
     * 修改密码
     */
    public function updatePassword(){
        $phone = $this->request()->getRequestParam('phone');
        $password = $this->request()->getRequestParam('password') ?? '';
        $newPassword = $this->request()->getRequestParam('newPassword') ?? '';
        if (empty($phone)) return $this->writeJson(201, null, null, 'phone 不能是空');
        if (empty($newPassword)) return $this->writeJson(201, null, null, 'newPassword 不能是空');
        if (empty($password)) return $this->writeJson(201, null, null, 'password 不能是空');

        $info = AdminNewUser::create()->where("phone = '{$phone}' and password = '{$password}'")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在或者密码错误');
        $info->update([
            'phone' => $phone,
            'password' => $newPassword,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

    /*
     * 用户冻结
     */
    public function updateUserStatus(){
        $phone = $this->request()->getRequestParam('phone');
        $status = $this->request()->getRequestParam('status');
        if (empty($phone)) return $this->writeJson(201, null, null, 'phone 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminNewUser::create()->where("phone = '{$phone}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'phone' => $phone,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

    /*
     * 新增用户（管理员）
     */
    public function addUser(){
        $user_name = $this->request()->getRequestParam('user_name');
        $password = $this->request()->getRequestParam('password');
        $email = $this->request()->getRequestParam('email');
        $phone = $this->request()->getRequestParam('phone');//type
        $type = $this->request()->getRequestParam('type');
        $company_id = $this->request()->getRequestParam('company_id');

        if (empty($phone)) return $this->writeJson(201, null, null, 'phone 不能是空');
        if (empty($user_name)) return $this->writeJson(201, null, null, 'user_name 不能是空');
        if (empty($password)) return $this->writeJson(201, null, null, 'password 不能是空');

        $insert = [
            'user_name'=>$user_name,
            'password'=>$password,
            'phone'=>$phone,
            'email'=>$email,
            'type'=>$type,
            'company_id'=>$company_id
        ];
        AdminNewUser::create()->data($insert)->save();
        return $this->writeJson(200, null, null, '添加成功');
    }

}