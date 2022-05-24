<?php

namespace App\HttpController\Service\AdminRole;

use App\HttpController\Models\Api\SupervisorPhoneEntName;
use App\HttpController\Models\Api\User;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class AdminPrivilegedUser extends ServiceBase
{
    use Singleton;

    private $roles;
    public $user_id  ;
    public function __construct() {
        parent::__construct();
    }

    // override User method
    public static function getByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = :username";
        $sth = $GLOBALS["DB"]->prepare($sql);
        $sth->execute(array(":username" => $username));
        $result = $sth->fetchAll();

        if (!empty($result)) {
            $privUser = new AdminPrivilegedUser();
            $privUser->user_id = $result[0]["user_id"];
            // $privUser->username = $username;
            // $privUser->password = $result[0]["password"];
            // $privUser->email_addr = $result[0]["email_addr"];
            $privUser->initRoles();
            return $privUser;
        } else {
            return false;
        }
    }

    // populate roles with their associated permissions
    protected function initRoles() {
        $this->roles = [];
        $sql = "SELECT 
                        user_role.role_id, 
                        roles.role_name 
                FROM admin_user_role as user_role
                JOIN admin_roles as roles ON user_role.role_id = roles.role_id
                WHERE user_role.user_id = ".$this->user_id;
          $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
          foreach($list as $dataItem){
            $this->roles[$dataItem["role_name"]] = AdminRole::getRolePerms($dataItem["role_id"]);              
          }
    }

    // check if user has a specific privilege
    public function hasPrivilege($perm) {
        foreach ($this->roles as $role) {
            if ($role->hasPerm($perm)) {
                return true;
            }
        }
        return false;
    }

}
