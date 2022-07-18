<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Documentation;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminRoles;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\User\UserService;
use EasySwoole\RedisPool\Redis;
use EasySwoole\Session\Session;
use App\HttpController\Models\AdminV2\AdminUserRole;

class DocumentationController extends ControllerBase
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

    public function getAll(){
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
            $value['email_for_show'] = AdminNewUser::hide(
                AdminNewUser::aesDecode($value['email'])
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
}