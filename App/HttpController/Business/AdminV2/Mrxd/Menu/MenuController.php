<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Menu;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;

class MenuController extends ControllerBase
{
    function onRequest(?string $action): ?bool
    {
        return parent::onRequest($action);
    }

    function afterAction(?string $actionName): void
    {
        parent::afterAction($actionName);
    }
    public function getAllowedMenu(){  
        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(true, $this->loginUserinfo['id'])
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
    public function addMenu(){
        $requestData = $this->getRequestData(); 
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
        $info = RequestApiInfo::create()->where('id',$requestData['id'])->get(); 
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
}