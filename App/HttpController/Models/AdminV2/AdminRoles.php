<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\AdminRole\AdminRole;
use App\HttpController\Service\Common\CommonService;

class AdminRoles extends ModelBase
{
    protected $tableName = 'admin_roles';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static  function  addRecordV2($info){

        if(
            self::findByName($info['role_name'])
        ){
            return  true;
        }

        return AdminRoles::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
            $res =  AdminRoles::create()->data([
                'role_name' => $requestData['role_name'],
                'status' => $requestData['status']?:1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminRoles::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = AdminRoles::create()
            ->where($whereArr)
            ->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page){
        $model = AdminRoles::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('role_id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  AdminRoles::create()
            ->where('id',$id)
            ->get();
        return $res;
    }
    public static function findByName($name){
        $res =  AdminRoles::create()
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = AdminRoles::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

}
