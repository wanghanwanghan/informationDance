<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

// use App\HttpController\Models\AdminRole;

class AdminUserRole extends ModelBase
{
    protected $tableName = 'admin_user_role';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function getRoleByUserId($userId){
        $roleRes = AdminUserRole::create()->where("user_id = ".$userId." ")->all();
        $newData = [];
        foreach($roleRes as &$roleItem){
            $roleDetailRes = AdminRoles::create()
                ->where("role_id = ".$roleItem['role_id']." ")
                ->get();
            $roleItem['role_cname'] = $roleDetailRes->getAttr("role_name");
            CommonService::getInstance()->log4PHP(
               [ 'role_cname' =>$roleItem['role_cname']]
            );
            $newData[] = $roleItem;

        }

        CommonService::getInstance()->log4PHP(
            [ 'roleRes' =>$newData]
         );
        return $newData;
    }

    public static function findByUserIdAndRole(
        $role_id,$user_id
    ){
        $res =  AdminUserRole::create()
            ->where([
                'role_id' => $role_id,  
                'user_id' => $user_id,   
            ])
            ->get();  
        return $res;
    }

    public static function findByUserId(
        $user_id
    ){
        $res =  AdminUserRole::create()
            ->where([
                'user_id' => $user_id,
            ])
            ->all();
        return $res;
    }

    public static function addRecord(
        $role_id,$user_id
    ){ 
        try {
           $res =  AdminUserRole::create()->data([
            'role_id' => $role_id,  
            'user_id' => $user_id,    
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'addCarInsuranceInfo Throwable continue',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    } 

}
