<?php

namespace App\HttpController\Business\AdminV2\Mrxd\User;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;

class UserController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {   
        // $this->setChckToken(true);
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }

    function userReg(): bool
    {
        return $this->writeJson();
    }

    /**
     * 用户登录
{
    "code": 200,
    "result": {
        "id": 1,
        "user_name": "tianyongshan",
        "password": "123456",
        "phone": "13269706193",
        "email": "",
        "token": "8bec0a81aa4260b6d0643cb33910b4f2faf7e58555b74afeb0252f58c7ab8c8a",
        "status": 1,
        "type": 1,
        "company_id": 0,
        "created_at": 0,
        "updated_at": 1653293491
    },
    "msg": null
}
     */
    public function userLogin()
    { 

        $username = $this->getRequestData('username','') ;
        $password = $this->getRequestData('password','') ;
        if (empty($username) || empty($password) ) {
            return $this->writeJson(201, null, null, '登录信息错误');
        }

        $info = AdminNewUser::create()
            ->where("user_name = '{$username}' and password = '{$password}'")
            ->get();
        if(empty($info)){
            return $this->writeJson(201, null, null, '账号密码错误');
        }else{
            $newToken = UserService::getInstance()->createAccessToken($info->phone, $info->password);
            $info->update(['token' => $newToken]);
            $info->token = $newToken;
            return $this->writeJson(200, [] , $info, null, '登录成功');
        }
    }

    /**
     * 修改密码
     */
    public function updatePassword(){
        $phone = $this->getRequestData('phone','');
        $password = $this->getRequestData('password','') ;
        $newPassword = $this->getRequestData('newPassword','') ;
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
       
        $phone = $this->getRequestData('phone');
        $status = $this->getRequestData('status');
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
     * 用户信息
     */
    public function getUserInfo(){ 
        return $this->writeJson(200, null, $this->loginUserinfo, '修改成功');
    }

    /*
     * 新增用户（管理员）
     */
    public function addUser(){
        $user_name = $this->getRequestData('user_name');
        $password = $this->getRequestData('password');
        $email = $this->getRequestData('email');
        $phone = $this->getRequestData('phone');//type
        $type = $this->getRequestData('type');
        $company_id = $this->getRequestData('company_id');

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