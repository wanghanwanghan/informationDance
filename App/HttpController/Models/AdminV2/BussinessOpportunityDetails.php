<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class BussinessOpportunityDetails extends ModelBase
{
    protected $tableName = 'bussiness_opportunity_details';

    static $status_init = 1;
    static $status_init_cname = '初始';


    public static function getStatusMap(){

        return [

        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByName($info['entName'],$info['upload_record_id'])
        ){
            return  true;
        }

        return BussinessOpportunityDetails::addRecord(
            $info
        );
    }


    public static function addRecord($requestData){
        try {
           $res =  BussinessOpportunityDetails::create()->data([
                'upload_record_id' => $requestData['upload_record_id'], //
                'entName' => $requestData['entName'], //
                'entCode' => $requestData['entCode'], //
                'mobile' => $requestData['mobile'], //
                'mobile_status' => $requestData['mobile_status']?:'', //
                'remark' => $requestData['remark']?:'', //
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
        $res =  BussinessOpportunityDetails::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = BussinessOpportunityDetails::findById($id);

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
        $model = BussinessOpportunityDetails::create()
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
        $model = BussinessOpportunityDetails::create();
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
        $res =  BussinessOpportunityDetails::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUploadId($id){
        $res =  BussinessOpportunityDetails::create()
            ->where('upload_record_id',$id)
            ->all();
        return $res;
    }

    public static function findByName($name,$upload_record_id){
        $res =  BussinessOpportunityDetails::create()
            ->where('upload_record_id',$upload_record_id)
            ->where('entName',$name)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = BussinessOpportunityDetails::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `bussiness_opportunity_details` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
