<?php

namespace App\HttpController\Service\AdminRole;

use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class AdminRole extends ServiceBase
{
    use Singleton;

    protected $permissions;

    protected function __construct() {
        $this->permissions = array();
    }

    // return a role object with associated permissions
    public static function getRolePerms($role_id) {
        $role = new AdminRole();
        $sql = "SELECT 
                    role_perm.menu_id 
                FROM admin_role_perm as role_perm 
                WHERE role_perm.role_id = $role_id
        "; 
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        foreach($list as $dataItem){
            $role->permissions[$dataItem["menu_id"]] = true;
        } 
        return $role;
    }

    // check if a permission is set
    public function hasPerm($permission) {
        return isset($this->permissions[$permission]);
    }

}
