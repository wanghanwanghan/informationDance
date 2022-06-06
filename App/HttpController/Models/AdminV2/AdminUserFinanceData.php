<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceData extends ModelBase
{
    /*
    
        该用户具体客户名单的收费
    */
    protected $tableName = 'admin_user_finance_data';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    public static function addRecord($requestData){ 
        try {
           $res =  AdminUserFinanceData::create()->data([
                'user_id' => $requestData['user_id'],  
                'entName' => $requestData['entName'],  
                'year' => $requestData['year'],  
                'finance_data_id' => $requestData['finance_data_id'],  
                'price' => $requestData['price'],  
                'price_type' => $requestData['price_type'],  
                'cache_end_date' => $requestData['cache_end_date'],  
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

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceData::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }


}
