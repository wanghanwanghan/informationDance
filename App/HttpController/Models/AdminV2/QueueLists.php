<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class QueueLists extends ModelBase
{
    protected $tableName = 'queue_lists';

    static $status_init  = 0;
    static $status_succees  = 5;
    static $status_failed  = 10;

    static $typle_finance = 5;


    public static function getStatusMap(){

        return [

        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByName($info['name'],$info['user_id'])
        ){
            return  true;
        }

        return QueueLists::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  QueueLists::create()->data([
                'name' => $requestData['name'],
                'desc' => $requestData['desc']?:'',
                'func_info_json' => $requestData['func_info_json'],
                'params_json' => $requestData['params_json']?:'',
                'type' => $requestData['type'],
                'remark' => $requestData['remark']?:'',
                'begin_date' => $requestData['begin_date']?:NULL,
                'msg' => $requestData['msg']?:'',
                'status' => $requestData['status']?:self::$status_init,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'message' => $e->getMessage()
                ])
            );
        }
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  QueueLists::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = QueueLists::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = QueueLists::create()
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

    public static function findByConditionV2($whereArr,$page,$size){
        $model = QueueLists::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$size)
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
        $res =  QueueLists::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByName($name,$user_id){
        $res =  QueueLists::create()
            ->where('user_id',$user_id)
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = QueueLists::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `queue_lists` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
