<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceUploadeHistory extends ModelBase
{
    protected $tableName = 'admin_user_finance_uploade_history';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 

    public static function addUploadRecord($requestData){
       
        try {
           $res =  AdminUserFinanceUploadeHistory::create()->data([
                'user_id' => $requestData['user_id'], 
                'file_path' => $requestData['file_path'],  
                'file_name' => $requestData['file_name'],  
                'title' => $requestData['title'],  
                'finance_config' => $requestData['finance_config'],  
                'reamrk' => $requestData['reamrk'],  
                'status' => 1,  
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

}
