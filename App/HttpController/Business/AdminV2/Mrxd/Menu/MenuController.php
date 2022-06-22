<?php

namespace App\HttpController\Business\AdminV2\Mrxd\Menu;

use App\HttpController\Business\AdminV2\Mrxd\ControllerBase;
use App\HttpController\Models\AdminV2\AdminMenuItems;
use App\HttpController\Models\AdminV2\AdminNewMenu;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Service\AdminRole\AdminPrivilegedUser;
use App\HttpController\Service\Common\CommonService;

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

    public function getRawMenus(){
        $name = $this->getRequestData('name','') ;
        $pageNo = $this->getRequestData('pageNo',1) ;
        $pageSize = $this->getRequestData('pageSize',10) ;
        $limit = ($pageNo-1)*$pageSize;
        $sql = "status = 1";
        if(!empty($name)){
            $sql .= " and name = '{$name}'";
        }
        $count = AdminMenuItems::create()->where($sql)->count();
        $res = AdminMenuItems::create()->where($sql." order by id desc limit {$limit},$pageSize ")->all();

        foreach($res as &$menuItem)
        {
            if($menuItem['parent_id'] <= 0){
                continue;
            };
            $tmpMenu = AdminMenuItems::findById($menuItem['parent_id']);
            $tmpMenu = $tmpMenu->toArray();
            $menuItem['pidRes'] = $tmpMenu;
            $menuItem['pidMenuName'] = $tmpMenu['name'];
        }
        $paging = [
            'page' => $pageNo,
            'pageSize' => $pageSize,
            'total' => $count,
            'totalPage' => (int)($count/$pageSize)+1,
        ];
        return $this->writeJson(
            200,
            $paging,
            $res
        );
    }

    public function getAllMenu(){  
        return $this->writeJson(
            200,
            [],
            AdminPrivilegedUser::getMenus(false,$this->loginUserinfo['id'])
        );
    }
    public function getMenuById(){  
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
            'link' => $this->request()->getRequestParam('link') ?? '',
            'icon' => $this->request()->getRequestParam('icon') ?? '',
        ]; 
        
        if (
            !$requestData['name']
//            ||!$requestData['method'] ||
//            !$requestData['class']
        ) {
            return $this->writeJson(201);
        } 
        
        AdminMenuItems::create()->data([
            'name' => $requestData['name'], 
            'method' => $requestData['method'], 
            'class' => $requestData['class'], 
            'remark' => $requestData['remark'],
            'link' => $requestData['link'],
            'icon' => $requestData['icon'],
            'parent_id' => intval($requestData['parent_id']), 
        ])->save();
        return $this->writeJson(200);
    }

    

     /**
     *  修改菜单
     */
    public function updateMenu(){
        $requestData = $this->getRequestData();
        CommonService::getInstance()->log4PHP(
            [
                 
                'requestData' => $requestData,
                $requestData['id']
            ]
        ); 
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
            'link' => $requestData['link'] ? $requestData['link']: $info['link'],
            'order' => $requestData['order'] ? $requestData['order']: $info['order'],
            'icon' => $requestData['icon'] ? $requestData['icon']: $info['icon'],
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
        $status = intval($this->getRequestData('status'));
        if (empty($id)) return $this->writeJson(201, null, null, '参数 不能是空');
        // if (empty($status)) return $this->writeJson(201, null, null, 'status 不能是空');
        $info = AdminMenuItems::create()->where("id = '{$id}' ")->get();
        if (empty($info)) return $this->writeJson(201, null, null, '用户不存在');
        $info->update([
            'id' => $id,
            'status' => $status,
        ]);
        return $this->writeJson(200, null, null, '修改成功');
    }

}