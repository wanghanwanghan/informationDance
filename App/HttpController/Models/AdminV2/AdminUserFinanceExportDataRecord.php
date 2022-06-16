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
    
    static $stateInit = 1;
    static $stateParsed = 5;
    static $stateExported = 10;

    public static function addExportRecord($requestData){
       
        try {
           $res =  AdminUserFinanceExportDataRecord::create()->data([
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

    public static function findByUserAndExportId($user_id,$export_id){
        $res =  AdminUserFinanceExportDataRecord::create()->where([
            'user_id' => $user_id,  
            'export_record_id' => $export_id,
            // 'status' => 1,  
        ])->all();

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportDataRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

}
