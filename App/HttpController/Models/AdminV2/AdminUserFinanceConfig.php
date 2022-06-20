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

    static $state_ok = 1;
    static $state_del = 5;

    static function getConfigByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,     
            'status' => 1,
        ])->get();
        return $res;
    }

    static function getConfigDataByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,
            'status' => 1,
        ])->get();
        return $res ? $res->toArray() : [];
    }


    public static function findById($id){
        $res =  AdminUserFinanceConfig::create()
            ->where('id',$id)
            ->get();
        return $res;
    }


    public static function setStatus($id,$status){
        $info = self::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

    static function addRecord($requestData){
        return AdminUserFinanceConfig::create()->data([
            'user_id' => $requestData['user_id'], 
            'annually_price' => $requestData['annually_price'],
             'needs_confirm' => $requestData['needs_confirm'],
            'annually_years' => $requestData['annually_years'],
            'single_year_charge_as_annual' => $requestData['single_year_charge_as_annual'],
            'normal_years_price_json' => $requestData['normal_years_price_json'],
            'allowed_total_years_num' => $requestData['allowed_total_years_num'],
            'cache' => $requestData['cache'],  
            'type' => $requestData['type'],  
            'allowed_fields' => $requestData['allowed_fields'],  
            'status' => $requestData['status'],  
        ])->save();  
    }


    static function addRecordV2($requestData){
        $res = self::getConfigByUserId($requestData['user_id']);
        if($res){
            return  $res->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function checkExportYearsNums($userid,$yearsNums){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   start',
                'params $userid '=> $userid,
                'params $yearsNums '=> $yearsNums,
            ])
        );

        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        $config = json_decode($data['finance_config'],true);

        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   get $config',
                'params $config '=> $config,
            ])
        );

        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   ',
                'params allowed_total_years_num '=> $config['allowed_total_years_num'],
                'params $yearsNums '=> $yearsNums,
            ])
        );
        if($config['allowed_total_years_num'] < $yearsNums){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkExportYearsNums  false ',
                    'params allowed_total_years_num '=> $config['allowed_total_years_num'],
                    'params $yearsNums '=> $yearsNums,
                ])
            );
        }
        return true ;
    }
}
