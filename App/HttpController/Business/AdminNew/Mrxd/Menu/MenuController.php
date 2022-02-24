<?php

namespace App\HttpController\Business\AdminNew\Mrxd\Menu;

use App\HttpController\Business\AdminNew\Mrxd\ControllerBase;
use App\HttpController\Models\AdminNew\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;

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

    /**
     *  客户权限的增删改查
     */
    public function addPower(){
        $form = $this->request()->getRequestParam();
        $pid = $form['pid'];
        $menu_name = $form['menu_name'];
        $sort_num = $form['sort_num'];
        if (empty($menu_name) || empty($menu_name)) return $this->writeJson(201);
        AdminNewMenu::create()->data([
            'pid' => $pid??0,
            'menu_name' => $menu_name,
            'sort_num' => $sort_num??0,
        ])->save();
        return $this->writeJson(200);
    }

    public function updatePower(){
        $form = $this->request()->getRequestParam();
        $id = $form['id'];
        $pid = $form['pid'];
        $menu_name = $form['menu_name'];
        $sort_num = $form['sort_num'];
        $info = RequestApiInfo::create()->where('id',$id)->get();
        $update = [];
        empty($id) ?: $update['id'] = $id;
        empty($pid) ?: $update['pid'] = $pid;
        empty($menu_name) ?: $update['menu_name'] = $menu_name;
        empty($sort_num) ?: $update['sort_num'] = $sort_num;
        $info->update($update);
        return $this->writeJson();
    }

    public function queryPower(){
        return AdminNewMenu::create()->all();
    }
}