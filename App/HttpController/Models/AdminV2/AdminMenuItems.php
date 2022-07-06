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

    public static function findById($id){
        $res =  AdminMenuItems::create()
            ->where('id',$id)
            ->get();
        return $res;
    }
    static function getMenusByParentId($parentId){
        $sql = "SELECT * FROM  admin_menu_items WHERE parent_id = $parentId AND `status` = 1  ORDER BY  `order` asc  " ;

        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'getMenusByParentId  ' ,
//                'params $parentId ' =>$parentId,
//                'params $sql ' =>$sql,
//                //'$list' =>$list,
//            ])
//        );
        return $list;
    }  
}
