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
        return $res;
    }

    static function getConfigDataByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,
            'status' => 1,
        ])->get();
        return $res ? $res->toArray() : [];
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

    public static function checkExportYearsNums($userid,$yearsNums){
        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        $config = json_decode($data['finance_config'],true);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums',
                '$userid' => $userid,
                '$yearsNums' => $yearsNums,
                '$config' => $config,
            ])
        );
        if($config['allowed_total_years_num'] < $yearsNums){
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkExportYearsNums false ',
                    '$userid' => $userid,
                    '$yearsNums' => $yearsNums,
                    '$config' => $config,
                    'allowed_total_years_num' => $config['allowed_total_years_num']
                ])
            );
        }
        return true ;
    }
}
