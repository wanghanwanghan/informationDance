<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
 

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_export_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 0;
    static $stateParsed = 5;
    static $stateExported = 10;

    public static function addExportRecord($requestData){ 
        try {
           $res =  AdminUserFinanceExportRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'price' => $requestData['price'],  
                'total_company_nums' => $requestData['total_company_nums'],  
                'config_json' => $requestData['config_json'],  
                'upload_record_id' => $requestData['upload_record_id'],  
                'reamrk' => $requestData['reamrk'],  
                'status' => $requestData['status'],  
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'addCarInsuranceInfo Throwable continue',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByIdAndFileName($user_id,$file_name){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,   
            // 'status' => 1,  
        ])->get(); 

        return $res;
    }

    public static function findByBatchNo($user_id,$batch){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'user_id' => $user_id,
            'batch' => $batch,
            // 'status' => 1,
        ])->get();

        return $res;
    }

    public static function findByQueue($queue_id){
        $res =  AdminUserFinanceExportRecord::create()->where([
//            'user_id' => $user_id,
            'queue_id' => $queue_id,
            // 'status' => 1,
        ])->all();

        return $res;
    }

    public static function findByQueueAndUploadId($queue_id,$upload_record_id){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'upload_record_id' => $upload_record_id,
            'queue_id' => $queue_id,
            // 'status' => 1,
        ])->get();

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

}
