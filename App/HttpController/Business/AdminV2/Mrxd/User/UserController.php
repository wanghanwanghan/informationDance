<?php

namespace App\HttpController\Business\AdminV2\Mrxd\User;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use App\HttpController\Models\AdminV2\AdminUserRole;

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

    public function getAllUser(){
        $user_name = $this->getRequestData('user_name','') ;
        $user_phone = $this->getRequestData('user_phone','') ;
        $pageNo = $this->getRequestData('pageNo',1) ;
        $pageSize = $this->getRequestData('pageSize',10) ;
        $status = $this->getRequestData('status','') ;
        $limit = ($pageNo-1)*$pageSize;
        $sql = "1=1";//status = 1
        if(!empty($user_name)){
            $sql .= " and user_name = '{$user_name}'";
        }
        if(!empty($user_phone)){
            $sql .= " and phone = '".AdminNewUser::aesEncode($user_phone)."'";
        }
        if(!empty($status)){
            $sql .= " and status = '{$status}'";
        }
        $count = AdminNewUser::create()->where($sql)->count();
        $list = AdminNewUser::create()
                ->where($sql." order by id desc limit {$limit},$pageSize ")
                ->field(['id', 'user_name', 'phone','email','money','status','created_at','updated_at'])
                ->all();
        $paging = [
            'page' => $pageNo,
            'pageSize' => $pageSize,
            'total' => $count,
            'totalPage' => (int)($count/$pageSize)+1,
        ];

        foreach ($list as &$value){
            $value['phone_for_show'] = AdminNewUser::hide(
                AdminNewUser::aesDecode($value['phone'])
            );

            $rolesRes = AdminUserRole::findByUserId($value['id']);
            $roles_ids_arr = array_column(
                $rolesRes,'role_id'
            );
            $value['roles_ids'] = json_encode($roles_ids_arr);
            $value['roles_ids_cnames'] = '';
            if(!empty($roles_ids_arr)){
                $Roles = AdminRoles::findByConditionV2(
                    [['field'=>'role_id','value'=>$roles_ids_arr,'operate'=>'IN']],1
                );
                $value['roles_ids_cnames'] = implode(
                    ',',
                    array_column(
                        $Roles['data'],'role_name'
                    )
                );
            }

        }
        return $this->writeJson(
            200,
            $paging,
            $list
        );
    }


    public function userLogin()
    { 

        $username = $this->getRequestData('username','') ;
        $password = $this->getRequestData('password','') ;
        if (empty($username) || empty($password) ) {
            return $this->writeJson(201, null, null, '登录信息错误');
        }
        $enCodePassword = AdminNewUser::aesEncode($password);

        $info = AdminNewUser::findByUserNameAndPwd(
            $username,$enCodePassword
        );
        if(empty($info)){
            return $this->writeJson(201, null, null, '账号密码错误');
        }else{
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'login succeed',
                    '$username' =>$username,
                    '$enCodePassword'=>$enCodePassword,
                ])
            );
            $newToken = UserService::getInstance()->createAccessToken(
                AdminNewUser::aesDecode($info->phone),
                AdminNewUser::aesDecode($info->password)
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'create new token ',
                    '$newToken' =>$newToken,
                    'AdminNewUser::aesDecode($info->phone)' => AdminNewUser::aesDecode($info->phone),
                    'AdminNewUser::aesDecode($info->password)' => AdminNewUser::aesDecode($info->password)
                ])
            );

            $info->update(['token' => $newToken]);
            //$info->token = $newToken;
            //$info->phone = AdminNewUser::aesDecode($info->phone );
            return $this->writeJson(200, [ ] , ['id' => $info->id,
                'user_name' => $info->user_name,
                'password' => $info->password,
                'phone' => AdminNewUser::aesDecode($info->phone ),
                'email' => $info->email,
                'token' => $info->token,
            ], null, '登录成功');
        }
    }
    public function signOut()
    { 
        $phone = $this->loginUserinfo['phone'];
        $info = AdminNewUser::findByPhone(
            AdminNewUser::aesEncode($phone)
        ) ;
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'token' => '',
        ]);
        return $this->writeJson(200, null, null, '成功');
    }

    /**
     * 修改密码
     */
    public function updatePassword(){
        $phone = $this->getRequestData('user_phone');
        $password = $this->getRequestData('password','') ;
        $newPassword = $this->getRequestData('newPassword','') ;
        if (empty($phone)) return $this->writeJson(201, null, null, 'user_phone 不能是空');
        if (empty($newPassword)) return $this->writeJson(201, null, null, 'newPassword 不能是空');
        if (empty($password)) return $this->writeJson(201, null, null, 'password 不能是空');

        $info = AdminNewUser::findByPhoneAndPwd(
            AdminNewUser::aesEncode($phone),
            AdminNewUser::aesEncode($password)
        );
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在或者密码错误');
        $info->update([
            'phone' => AdminNewUser::aesEncode($phone),
            'password' =>AdminNewUser::aesEncode($newPassword)
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

    /**
     *  修改用户信息
     */
    public function updateUserInfo(){
        $requestData = $this->getRequestData(); 
        $info = AdminNewUser::create()->where('id',$requestData['id'])->get();  
        $info->update([
            'id' => $requestData['id'],
            'user_name' => $requestData['user_name'] ? $requestData['user_name']: $info['user_name'],
            'password' => $requestData['password'] ? AdminNewUser::aesEncode($requestData['password']): $info['password'],
            'phone' => $requestData['phone'] ? AdminNewUser::aesEncode($requestData['phone']): $info['phone'],
            'email' => $requestData['email'] ? $requestData['email']: $info['email'],
        ]);
        return $this->writeJson();
    }

    /*
     * 用户冻结
     */
    public function updateUserStatus(){
       
        $phone = $this->getRequestData('user_phone');
        $status = $this->getRequestData('status');
        if (empty($phone)) return $this->writeJson(201, null, null, 'phone 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminNewUser::findByPhone(
             AdminNewUser::aesEncode($phone)
        );
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

     /*
     * 用户信息
     */
    public function getUserInfo(){ 
        $userInfo = $this->loginUserinfo;
        $userInfo['roles_info'] = (new AdminUserRole())->getRoleByUserId($this->loginUserinfo['id']);
        return $this->writeJson(200, null, $this->loginUserinfo, '成功');
    }

    /*
     * 新增用户（管理员）
     */
    public function addUser(){
        $user_name = $this->getRequestData('user_name');
        $password = $this->getRequestData('password');
        $email = $this->getRequestData('email');
        $phone = $this->getRequestData('user_phone');//type
        $type = $this->getRequestData('type');
        $company_id = $this->getRequestData('company_id');

        if (empty($phone)){
            return $this->writeJson(201, null, null, 'phone 不能是空');
        }
        if (empty($user_name)) {
            return $this->writeJson(201, null, null, 'user_name 不能是空');
        }
        if (empty($password)){
            return $this->writeJson(201, null, null, 'password 不能是空');
        }

        $enCodePassword = AdminNewUser::aesEncode($password);
        $enCodePhone = AdminNewUser::aesEncode($phone);

        if (
            AdminNewUser::findByPhone($enCodePhone)
        ) {
            return $this->writeJson(201, null, null, 'phone 已存在');
        }

        AdminNewUser::addRecordV2(
            [
                'user_name'=>$user_name,
                'password'=>$enCodePassword,
                'phone'=>$enCodePhone,
                'email'=>$email,
                'type'=>$type,
                'company_id'=>$company_id
            ]
        );

        return $this->writeJson(200, null, null, '添加成功');
    }

    public function decrypt(){
        $encryption = $this->getRequestData('encryption');
        if(empty($encryption)){
            return $this->writeJson(201, null, null, '参数缺失');
        }
        return $this->writeJson(200, null, AdminNewUser::aesDecode($encryption), '添加成功');
    }

}