<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
 

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportDataRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_export_data_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 0;
    static $stateParsed = 5;
    static $stateExported = 10;

    public static function addExportRecord($requestData){
       
        try {
           $res =  AdminUserFinanceUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'export_record_id' => $requestData['export_record_id'],   
                'user_finance_data_id' => $requestData['user_finance_data_id'],   
                'price' => $requestData['price'],   
                'detail' => $requestData['detail'],   
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
        $res =  AdminUserFinanceUploadRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,   
            // 'status' => 1,  
        ])->get(); 

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

}