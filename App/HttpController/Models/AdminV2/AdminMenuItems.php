<?php

namespace App\HttpController\Models\AdminV2;
use App\HttpController\Service\CreateConf;


use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

class AdminMenuItems extends ModelBase
{
    protected $tableName = 'admin_menu_items';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static function getMapedMenus(){
        // 加缓存
        $mapedMenus = [];
        $parentMenu = self::getMenusByParentId(0);
        foreach($parentMenu as $parentMenuItem){
            $mapedMenus[$parentMenuItem['id']] = $parentMenuItem;
            $childMenus =  self::getMenusByParentId($parentMenuItem['id']);
            // CommonService::getInstance()->log4PHP('childMenus '.json_encode($childMenus)); 
            foreach($childMenus as $childMenuItem){
                $mapedMenus[$parentMenuItem['id']]['child_menus'][$childMenuItem['id']] = $childMenuItem;
            }
        }
        // CommonService::getInstance()->log4PHP('mapedMenus '.json_encode($mapedMenus));  
        return  $mapedMenus; 
    }

    static function getMenusByParentId($parentId){
        $sql = "SELECT * FROM  admin_menu_items WHERE parent_id = $parentId AND `status` = 1  " ;
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $list;
    }
}
