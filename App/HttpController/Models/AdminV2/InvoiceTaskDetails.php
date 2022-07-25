<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class InvoiceTaskDetails extends ModelBase
{

    protected $tableName = 'invoice_task_details';

    static  $state_init = 1;
    static  $state_init_cname =  '';

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByRwh($info['rwh'],$info['invoice_task_id'])
        ){
            return  true;
        }

        return InvoiceTaskDetails::addRecord(
            $info
        );
    }

    public static function findByRwh($rwh,$invoice_task_id){
        $res =  InvoiceTaskDetails::create()
            ->where('rwh',$rwh)
            ->where('invoice_task_id',$invoice_task_id)
            ->get();
        return $res;
    }

    public static function addRecord($requestData){
        try {
           $res =  InvoiceTaskDetails::create()->data([
                'invoice_task_id' => $requestData['invoice_task_id'],
                'fplx' => $requestData['fplx'],
               'kprqq' => $requestData['kprqq'],
               'kprqz' => $requestData['kprqz'],
               'requuid' => $requestData['requuid'],
               'rwh' => $requestData['rwh'],
               'sjlx' => $requestData['sjlx'],
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
        $res =  InvoiceTaskDetails::create()
            ->where($whereArr)
            ->all();
        return $res;
    }



    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = InvoiceTaskDetails::create()
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
        $model = InvoiceTaskDetails::create();
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
        $res =  InvoiceTaskDetails::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = InvoiceTaskDetails::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `invoice_task_details` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
