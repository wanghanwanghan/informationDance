<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class CompanyCarInsuranceStatusInfo extends ModelBase
{
    protected $tableName = 'company_car_insurance_status_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static $status_init = 0;
    static $status_part_auth_done = 5;
    static $status_all_auth_done = 10;
    static $status_all_done = 15;
    public static function getStatusMap(){
        return [
            0 => '待处理',
            5 => '部分授权完成',
            10 => '全部授权完成',
            15 => '已完成',
        ];
    }

    public static function ifHasAuthAll($id){
        $CompanyCarInsuranceStatusInfo = CompanyCarInsuranceStatusInfo::create()->where( 
            [
                'id' => $id
            ]
        )->get(); 
        if(
            CompanyCarInsuranceStatusInfo::ifHasAuthAll(
                $CompanyCarInsuranceStatusInfo->getAttr('status')
            ) 
        ){

        }
        return ($CompanyCarInsuranceStatusInfo->getAttr('status') == self::$status_all_auth_done);
    }

    public static function setIsOk($id){
        
        return   CompanyCarInsuranceStatusInfo::create()
                ->where(['id' => $id])
                ->update([
                    'status' => self::$status_all_done, 
                ]); 
    }
}