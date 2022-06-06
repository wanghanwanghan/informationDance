<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Permission;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\AdminV2\AdminPermissions;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;

class PermissionController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    public function updateRolePermissions(){  
        
        return $this->writeJson(
            200,
            [],
            AdminPermissions::create()->all()
        );
    }

     
    public function updateRolePermission(){  
        $id = $this->request()->getRequestParam('id') ?? '';
        $requestData = $this->getRequestData(); 
        if($id <= 0){
            return $this->writeJson(
                201,
                [],
                '参数错误（'.$id.'）'
            );
        }
        $info = AdminMenuItems::create()->where('id',$id)->all(); 
         
        return $this->writeJson(
            200,
            [],
            $info[0]?$info[0]:[]
        );
    }

     

    /**
     *  增加菜单
     */
    public function addMenu(){
        // $requestData = $this->getRequestData(); 
        $requestData = [
            'id' => $this->request()->getRequestParam('id') ?? '',
            'name' => $this->request()->getRequestParam('name') ?? '',
            'method' => $this->request()->getRequestParam('method') ?? '',
            'class' => $this->request()->getRequestParam('class') ?? '',
            'remark' => $this->request()->getRequestParam('remark') ?? '',
            'parent_id' => $this->request()->getRequestParam('parent_id') ?? '',
        ]; 
        
        if (
            !$requestData['name'] ||
            !$requestData['method'] ||
            !$requestData['class'] 
        ) {
            return $this->writeJson(201);
        } 
        
        AdminMenuItems::create()->data([
            'name' => $requestData['name'], 
            'method' => $requestData['method'], 
            'class' => $requestData['class'], 
            'remark' => $requestData['remark'], 
            'parent_id' => intval($requestData['parent_id']), 
        ])->save();
        return $this->writeJson(200);
    }

    

     /**
     *  修改菜单
     */
    public function updateMenu(){
        $requestData = $this->getRequestData(); 
        $info = AdminMenuItems::create()->where('id',$requestData['id'])->get(); 
        if(!$info){
            return $this->writeJson(
                201,
                [],
                '参数错误（'.$requestData['id'].'）'
            );
        }
        $info->update([
            'id' => $requestData['id'],
            'name' => $requestData['name'] ? $requestData['name']: $info['name'],
            'method' => $requestData['method'] ? $requestData['method']: $info['method'],
            'class' => $requestData['class'] ? $requestData['class']: $info['class'],
            'parent_id' => $requestData['parent_id'] ? $requestData['parent_id']: $info['parent_id'],
            'remark' => $requestData['remark'] ? $requestData['remark']: $info['remark'],
        ]);
        return $this->writeJson();
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }

    /*
     * 菜单冻结
     */
    public function updateMenuStatus(){ 
        $id = $this->getRequestData('id');
        $status = $this->getRequestData('status');
        if (empty($phone)) return $this->writeJson(201, null, null, '参数 不能是空');
        if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminMenuItems::create()->where("id = '{$id}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'id' => $id,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

}