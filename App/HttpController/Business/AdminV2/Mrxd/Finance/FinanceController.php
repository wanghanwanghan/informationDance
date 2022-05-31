<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Charge;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Models\AdminV2\AdminRoles;

class FinanceController extends ControllerBase
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
            'role_name' => $requestData['name'], 
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
        $info = AdminRoles::create()->where('role_id',$requestData['role_id'])->get(); 
        $info->update([
            'role_id' => $requestData['role_id'],
            'role_name' => $requestData['role_name'] ? $requestData['name']: $info['role_name'],
            'remark' => $requestData['remark'] ? $requestData['remark']: $info['remark'],
        ]);
        return $this->writeJson();
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }

    /*
     * 角色冻结
     */
    public function updateRoleStatus(){
       
        $role_id = $this->getRequestData('role_id');
        $status = $this->getRequestData('status');
        if (empty($phone)) return $this->writeJson(201, null, null, '参数 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminRoles::create()->where("role_id = '{$role_id}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'role_id' => $role_id,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

}