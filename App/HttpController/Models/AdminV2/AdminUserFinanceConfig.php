<?php

namespace App\HttpController\Models\AdminV2;
use App\HttpController\Service\CreateConf;


use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

class AdminUserFinanceConfig extends ModelBase
{
    protected $tableName = 'admin_user_finance_config';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
 
    static function getConfigByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,     
            'status' => 1,  
        ])->all();  
        return $res[0] ? json_encode( $res[0]) : '';
    }

    static function addRecord($requestData){
        return AdminUserFinanceConfig::create()->data([
            'user_id' => $requestData['user_id'], 
            'annually_price' => $requestData['annually_price'],  
            'annually_years' => $requestData['annually_years'],  
            'normal_years_price_json' => $requestData['normal_years_price_json'],  
            'cache' => $requestData['cache'],  
            'type' => $requestData['type'],  
            'allowed_fields' => $requestData['allowed_fields'],  
            'status' => $requestData['status'],  
        ])->save();  
    }
      
}