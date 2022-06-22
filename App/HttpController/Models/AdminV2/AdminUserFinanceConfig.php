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

    public $exportYearsOk = true;

    function setExportYearsOk(){
        $this->exportYearsOk = true;
        return $this;
    }
    function setExportYearsNotOk(){
        $this->exportYearsOk = false;
        return $this;
    }


    function checkExportYearsNumsV2(){

        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        if($data['allowed_total_years_num'] < $yearsNums){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkExportYearsNums   false',
                    'params $userid '=> $userid,
                    'params $yearsNums '=> $yearsNums,
                    'allowed_total_years_num' =>$data['allowed_total_years_num'],
                ])
            );
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   true',
                'params $userid '=> $userid,
                'params $yearsNums '=> $yearsNums,
                'allowed_total_years_num' =>$data['allowed_total_years_num'],
            ])
        );
        return true ;

        return $this;
    }


    static function getConfigByUserId($userId){
        $res =  AdminUserFinanceConfig::create()->where([
            'user_id' => $userId,     
            'status' => 1,
        ])->get();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig  getConfigByUserId start',
                '$userId'=>$userId,
                '$res'=>$res,
            ])
        );
        return $res;
    }

    static function getDailyMaxNums($userId){
        $res =  self::getConfigByUserId($userId);

        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig  getDailyMaxNums  start',
                '$userId'=>$userId,
                'max_daily_nums'=>$res->getAttr('max_daily_nums'),
            ])
        );

        return $res->getAttr('max_daily_nums');
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceConfig setStatus',
                 '$id'=>$id,
                '$status' =>$status,
            ])
        );
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
            'max_daily_nums' => $requestData['max_daily_nums'],
            'sms_notice_value' => $requestData['sms_notice_value'],
        ])->save();  
    }


    static function addRecordV2($requestData){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'finance config    addRecordV2',
                'params $requestData '=> $requestData,
            ])
        );

        $res = self::getConfigByUserId($requestData['user_id']);
        if($res){
            return  $res->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function checkExportYearsNums($userid,$yearsNums){

        $info = self::getConfigByUserId($userid);
        $data = $info->toArray();
        if($data['allowed_total_years_num'] < $yearsNums){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkExportYearsNums   false',
                    'params $userid '=> $userid,
                    'params $yearsNums '=> $yearsNums,
                    'allowed_total_years_num' =>$data['allowed_total_years_num'],
                ])
            );
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkExportYearsNums   true',
                'params $userid '=> $userid,
                'params $yearsNums '=> $yearsNums,
                'allowed_total_years_num' =>$data['allowed_total_years_num'],
            ])
        );
        return true ;
    }
}
