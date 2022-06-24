<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class DataModelExample extends ModelBase
{

    protected $tableName = 'data_example';

    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByBatch($info['batch'])
        ){
            return  true;
        }

        return AdminUserFinanceExportDataQueue::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  AdminUserFinanceExportDataQueue::create()->data([
                'upload_record_id' => $requestData['upload_record_id'],
                'status' => $requestData['status']?:1,
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

    public static function findAllByCondition($whereArr){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = AdminUserFinanceExportDataQueue::create()
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
        $model = AdminUserFinanceExportDataQueue::create();
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
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}
