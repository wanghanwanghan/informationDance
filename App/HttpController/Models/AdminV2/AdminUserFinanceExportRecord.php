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
                'queue_id' => $requestData['queue_id'],
                'batch' => $requestData['batch'],
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

    static  function  addRecordV2($dataItem){
        $exportRes = AdminUserFinanceExportRecord::findByQueue($dataItem['queue_id']);
        if($exportRes){
            return $exportRes->getAttr('id');
        }

        $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addExportRecord(
            [
                'user_id' => $dataItem['user_id'],
                'price' => $dataItem['price'],
                'total_company_nums' => 0,
                'config_json' => $dataItem['config_json'],
                'upload_record_id' => $dataItem['upload_record_id'],
                'reamrk' => $dataItem['upload_record_id'],
                'status' => $dataItem['status'],
                'queue_id' => $dataItem['queue_id'],
                'batch' => $dataItem['batch'],
            ]
        );
        if(
            $AdminUserFinanceExportRecordId <= 0
        ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    '设置导出记录' ,
                    '$AdminUserFinanceExportRecordId' =>$AdminUserFinanceExportRecordId,
                ])
            );
        }
        return  $AdminUserFinanceExportRecordId;
    }
}
