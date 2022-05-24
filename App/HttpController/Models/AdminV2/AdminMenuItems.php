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

    static function getMenusByParentId($parentId){
        $sql = "SELECT * FROM  admin_menu_items WHERE parent_id = $parentId AND `status` = 1  " ;
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $list;
    }  
}
