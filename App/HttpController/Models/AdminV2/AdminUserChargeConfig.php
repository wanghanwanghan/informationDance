<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserChargeConfig extends ModelBase
{

    protected $tableName = 'admin_user_charge_config';
    static  $can_pull_data_yes = 1;
    static  $can_pull_data_yes_cname =  '允许拉取数据';

    static  $can_pull_data_no = 0;
    static  $can_pull_data_no_cname =  '不允许拉取数据';

    public static function disableUser($user_id){
        $info = self::findByUser($user_id);

        return $info->update([
            'can_pull_data' => self::$can_pull_data_no,
        ]);
    }

    public static function setAllowedDailyNums($user_id,$nums){
        $info = self::findByUser($user_id);

        return $info->update([
            'allowed_daily_nums' => $nums,
        ]);
    }

    public static function setAllowedTotalNums($user_id,$allowed_total_nums){
        $info = self::findByUser($user_id);

        return $info->update([
            'allowed_total_nums' => $allowed_total_nums,
        ]);
    }

    public static function setUsedTotalNums($user_id,$total_used_nums){
        $info = self::findByUser($user_id);

        return $info->update([
            'total_used_nums' => $total_used_nums,
        ]);
    }

    public static function setDailyUsedNums($user_id,$daily_used_nums){
        $info = self::findByUser($user_id);

        return $info->update([
            'daily_used_nums' => $daily_used_nums,
        ]);
    }

    public static function addRecord($requestData){
        try {
           $res =  AdminUserChargeConfig::create()->data([
                'user_id' => $requestData['user_id'],  
                'can_pull_data' => $requestData['can_pull_data']?:1,
                'allowed_daily_nums' => $requestData['allowed_daily_nums']?:0,
                'daily_used_nums' => $requestData['daily_used_nums']?:0,
                'allowed_total_nums' => $requestData['allowed_total_nums']?:0,
                'total_used_nums' => $requestData['total_used_nums']?:0,
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,

           ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceData sql err',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserChargeConfig::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();
        return $res;
    }

    // 用完今日余额的
    public static function findRunOutDailyBanance(){
        $Sql = " select *  
                            from  
                        `admin_user_charge_config` 
                            where 
                                allowed_daily_nums > 0 AND
                                daily_used_nums > 0  AND
                                daily_used_nums >= allowed_daily_nums 
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        return $data;
    }

    //
    public static function checkIfUserIsValid($userId){
        if(
            !self::checkIfUserHasRunOutDailyBanance(
                $userId
            )
        ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkIfUserHasRunOutDailyBanance  err',

                ])
            );
        }
        return true ;
    }

    // 用完今日余额的
    public static function checkIfUserHasRunOutDailyBanance($userId){
        $Sql = " select *  
                            from  
                        `admin_user_charge_config` 
                            where 
                                allowed_daily_nums > 0 AND
                                daily_used_nums > 0  AND
                                daily_used_nums >= allowed_daily_nums AND 
                                user_id = $userId
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        return empty($data)? false:true;
    }

    public static function findById($id){
        $res =  AdminUserChargeConfig::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUser($userId){
        $res =  AdminUserChargeConfig::create()
            ->where([
                'user_id' => $userId,
            ])
            ->get();
        return $res;
    }

}
