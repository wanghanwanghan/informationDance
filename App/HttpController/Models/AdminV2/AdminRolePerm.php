<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\AdminRole\AdminRole;
use App\HttpController\Service\Common\CommonService;

class AdminRolePerm extends ModelBase
{
    protected $tableName = 'admin_role_perm';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public static function findByMenuIdAndRole(
        $role_id,$menu_id
    ){
        $res =  AdminRolePerm::create()
            ->where([
                'role_id' => $role_id,  
                'menu_id' => $menu_id,   
            ])
            ->get();  
        return $res;
    }

    public static function addRecord(
        $role_id,$menu_id
    ){ 
        try {
           $res =  AdminRolePerm::create()->data([
                'role_id' => $role_id,  
                'menu_id' => $menu_id,   
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
