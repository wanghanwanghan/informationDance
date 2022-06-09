<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
 

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceUploadDataRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_upload_data_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateInitCname = '初始化';
    static $stateHasCalculatePrice = 5;
    static $stateHasCalculatePriceCname = '已计算价格';
    static $stateExported = 10;

    public static function addUploadRecord($requestData){ 
        try {
           $res =  AdminUserFinanceUploadDataRecord::create()->data([
                'user_id' => $requestData['user_id'],  
                'record_id' => $requestData['record_id'],  
                'user_finance_data_id' => $requestData['user_finance_data_id'],  
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadDataRecord sql err',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByUserIdAndRecordIdAndFinanceId(
        $user_id,$record_id,$user_finance_data_id
    ){ 
        $res =  AdminUserFinanceUploadDataRecord::create()->where([
            'user_id' => $user_id,  
            'record_id' => $record_id,  
            'user_finance_data_id' => $user_finance_data_id,  
        ])->get(); 

        return $res;
    }

    public static function findByUserIdAndRecordId(
        $user_id,$record_id,$status,$fieldsArr = []
    ){ 
        if(empty($fieldsArr)){
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,  
                'record_id' => $record_id,   
                'status' => $status,   
            ]) 
            ->all(); 
        } 
        else{
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,  
                'record_id' => $record_id,   
                'status' => $status,   
            ])
            ->field($fieldsArr)
            ->all(); 
        }

        return $res;
    }

    public static function findFinanceDataByUserIdAndRecordId(
        $user_id,$record_id,$status
    ){ 
        $res =  AdminUserFinanceUploadDataRecord::create()->where([
            'user_id' => $user_id,  
            'record_id' => $record_id,   
            'status' => $status,   
        ])->field(['id','user_finance_data_id'])
        ->all(); 

        $user_finance_data_ids = array_column($res,'user_finance_data_id');
        $dataRes =  AdminUserFinanceUploadDataRecord::create()->where(
            'id',$user_finance_data_ids,'IN'
        )->field(['id','user_finance_data_id'])
        ->all(); 

        return $res;
    }


    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceUploadDataRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function updateStatusById(
        $id,$status
    ){ 
        $res =  AdminUserFinanceUploadDataRecord::create()->where([
            'id' => $id,   
        ])->get(); 

        return $res->update([
            'id' => $id,
            'status' => $status 
        ]);
    }

}
