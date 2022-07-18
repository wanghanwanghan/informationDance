<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class Documentation extends ModelBase
{
    protected $tableName = 'documentation';

    public static function addRecord($requestData){
        try {
           $res =  Documentation::create()->data([
                'name' => $requestData['name']?:'',
                'type' => $requestData['type']?:'0',
               'content' => $requestData['content']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function addRecordV2($requestData){
        $oldRes = self::findByName($requestData['name']);
       if(
           $oldRes
       ){
        return  $oldRes->getAttr('id');
       }
       return  self::addRecord($requestData);
    }

    public static function findAllByCondition($whereArr){
        $res =  Documentation::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByName($name){
        $res =  Documentation::create()
            ->where('name',$name)
            ->get();
        return $res;
    }


    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = Documentation::create()
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
        $model = Documentation::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  Documentation::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }
}
