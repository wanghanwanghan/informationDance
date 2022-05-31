<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Role;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;

class RoleController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    public function getAllRoles(){ 
        return $this->writeJson(
            200,
            [],
           AdminRoles::create()->where("status = 1")->all()
        );
    }

    public function getAllMenu(){  
        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(false,$this->loginUserinfo['id'])
        );
    }

    /**
     *  增加菜单
     */
    public function addRole(){
        $requestData = $this->getRequestData(); 
        if (
            !$requestData['name'] ||
            !$requestData['remark']  
        ) {
            return $this->writeJson(201);
        } 
        AdminRoles::create()->data([
            'role_name' => $requestData['role_name'], 
            'remark' => $requestData['remark'],  
            'status' => 1,  
        ])->save();
        return $this->writeJson(200);
    }

     /**
     *  修改菜单
     */
    public function updateRole(){
        $requestData = $this->getRequestData(); 
        $info = RequestApiInfo::create()->where('role_id',$requestData['role_id'])->get(); 
        $info->update([
            'role_id' => $requestData['role_id'],
            'role_name' => $requestData['role_name'] ? $requestData['role_name']: $info['role_name'],
            'remark' => $requestData['remark'] ? $requestData['remark']: $info['remark'],
        ]);
        return $this->writeJson();
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }
}