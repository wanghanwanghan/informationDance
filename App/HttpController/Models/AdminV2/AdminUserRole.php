<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
// use App\HttpController\Models\AdminRole;

class AdminUserRole extends ModelBase
{
    protected $tableName = 'admin_user_role';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function getRoleByUserId($userId){
        $roleRes = AdminUserRole::create()->where("user_id = ".$userId." ")->all();
        foreach($roleRes as &$roleItem){
            $roleDetailRes = AdminRoles::create()
                ->where("role_id = ".$roleItem['role_id']." ")
                ->get();
            $roleItem['role_cname'] = $roleDetailRes->getAttr("role_name");
        }
        return $roleRes;
    }

}
