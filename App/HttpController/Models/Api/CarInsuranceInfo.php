<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;


class CarInsuranceInfo extends ModelBase
{
    protected $tableName = 'car_insurance_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static function getAuthedList($entId){
        return  CarInsuranceInfo::create()->where([
            'entId' => $entId,
            'status' => 5,
        ])->all(); 
    }

    static function checkIfFileExists($remoteFile){
        // Open file
        $handle = @fopen($remoteFile, 'r');

        // Check if file exists
        if(!$handle){
           return false;
        }else{
           return true;
        }
    }
    static function getAuthedFileUrl($entId){
        $allCarsInfo = CarInsuranceInfo::getAuthedList($entId);
        if (empty($allCarsInfo)) { 
             return [];
        } 

        $resData = [];
        foreach($allCarsInfo as $CarInfo){
            $res = DianZiQianAuth::create()->where([
                'id' =>  $CarInfo->getAttr('auth_res_id')
            ])->get();

            $tmp = [];
            if (
                !empty($res->getAttr('entDownloadUrl')) && 
                self::checkIfFileExists($res->getAttr('entDownloadUrl'))
            ) {
                 
                $tmp['entDownloadUrl'] = $res->getAttr('entDownloadUrl');
                
            } 

            if (
                !empty($res->getAttr('personalDownloadUrl')) && 
                self::checkIfFileExists($res->getAttr('personalDownloadUrl'))
            ) {
                $tmp['entDownloadUrl'] = $res->getAttr('personalDownloadUrl');
            }

            $resData[]  =  $tmp;
        } 
        return $resData;
    }
}