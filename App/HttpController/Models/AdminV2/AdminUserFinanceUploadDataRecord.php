<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceUploadDataRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_upload_data_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $chargeTypeAnnually = 5;
    static $chargeTypeAnnuallyCname = '包年';
    static $chargeTypeByYear = 10;
    static $chargeTypeByYearCname = '按年';

    public static function addUploadRecord($requestData){
        $infoArr = [
            'user_id' => $requestData['user_id'],
            'record_id' => $requestData['record_id'],
            'user_finance_data_id' => $requestData['user_finance_data_id'],
            'reamrk' => $requestData['reamrk']?:'',
            'status' => $requestData['status']?:1,
        ];
        try {
           $res =  AdminUserFinanceUploadDataRecord::create()->data($infoArr)->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        __CLASS__.__FUNCTION__ .' false',
                        '$infoArr' =>$infoArr,
                    ]
                )
            );
        }  

        return $res;
    }
    static  function  addRecordV2($infoArr){
        $UploadDataRecord = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdAndFinanceId(
            $infoArr['user_id'],
            $infoArr['record_id'] ,
            $infoArr['user_finance_data_id']
        );

        if($UploadDataRecord){
            return $UploadDataRecord->getAttr('id');
        }
        $AdminUserFinanceUploadDataRecordId = AdminUserFinanceUploadDataRecord::addUploadRecord(
            $infoArr
        );

        if($AdminUserFinanceUploadDataRecordId <= 0){
            return false;
        }

        return  true;
    }
    public static function findByUserIdAndRecordIdAndFinanceId(
        $user_id,$record_id,$user_finance_data_id
    ){

        $res =  AdminUserFinanceUploadDataRecord::create()->where([
            'user_id' => $user_id,  
            'record_id' => $record_id,  
            'user_finance_data_id' => $user_finance_data_id,  
        ])->get(); 

        return $res;
    }

    public static function findByUserIdAndRecordId(
        $user_id,$record_id,$status,$fieldsArr = []
    ){ 
        if(empty($fieldsArr)){
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,  
                'record_id' => $record_id,   
                'status' => $status,   
            ]) 
            ->all(); 
        } 
        else{
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,  
                'record_id' => $record_id,   
                'status' => $status,   
            ])
            ->field($fieldsArr)
            ->all(); 
        }

        return $res;
    }

    public static function findByUserIdAndRecordIdV2(
        $user_id,$record_id,$fieldsArr = []
    ){
        if(empty($fieldsArr)){
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,
                'record_id' => $record_id,
            ])
            ->all();
        }
        else{
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,
                'record_id' => $record_id,
            ])
                ->field($fieldsArr)
                ->all();
        }

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceUploadDataRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function updateStatusById(
        $id,$status
    ){
        $info = AdminUserFinanceUploadDataRecord::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'status' => $status,
        ]);
    }


    public static function findById($id){
        $res =  AdminUserFinanceUploadDataRecord::create()
            ->where('id',$id)
            ->get();
        return $res;
    }


    public static function updatePriceType($info){

        $res = self::findById($info['id']);
        $res2 = $res->update([
            'id' => $info['id'],
            'price' => $info['price'],
            'price_type' => $info['price_type'],
            'price_type_remark' => $info['price_type_remark'],
            'charge_year' => $info['charge_year'],
            'charge_year_start' => $info['charge_year_start'],
            'charge_year_end' => $info['charge_year_end'],
        ]);
        return $res2;
    }

    public static function updateRealPrice($id,$realPrice,$priceRemark){

        $res = self::findById($id);
        $res2 = $res->update([
            'real_price' => $realPrice,
            'real_price_remark' => $priceRemark,
        ]);

        return $res2;
    }

    //  设置收费类型|按包年收费 还是按单年收费
    public static function updateChargeInfo($id,$uploadId){

        $uploadInfo = AdminUserFinanceUploadRecord::findById($uploadId);
        $uploadInfo = $uploadInfo->toArray();

        $dataInfo = self::findById($id);
        $dataInfo = $dataInfo->toArray();

        //用户的配置
        $finance_config = json_decode($uploadInfo['finance_config'],true);

        //用户该次选择的年限
        $selectYears = json_decode($uploadInfo['years'],true);

        //用户财务其他信息
        $user_finance_data = AdminUserFinanceData::findById($dataInfo['user_finance_data_id']);

        //按包年计费？按年计费
        $annulYears = json_decode($finance_config['annually_years'],true);
        sort($annulYears);

        //如果都不属于包年年度 当然要按照单年计算
        if(
            !in_array($user_finance_data['year'],$annulYears)
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'not in annul years .charge by single year ',
                    'year' => $user_finance_data['year'],
                    '$annulYears' => $annulYears,
                    'upload_data_id' => $id,
                    '$uploadId' =>$uploadId,
                    'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                ])
            );
            return self::updatePriceType(
                [
                    'id' => $id,
                    'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                    'price_type' => self::$chargeTypeByYear,
                    'price_type_remark' =>  '不属包年年度,按单年计算',
                    'charge_year' => $user_finance_data['year'],
                    'charge_year_start' => '',
                    'charge_year_end' => '',
                ]
            );
        }

        //默认是包年
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'default  .charge by annual year ',
                'year' => $user_finance_data['year'],
                '$annulYears' => $annulYears,
                'upload_data_id' => $id,
                '$uploadId' =>$uploadId,
                'price' => $finance_config['annually_price'],
            ])
        );
        self::updatePriceType(
            [
                'id' => $id,
                'price' => $finance_config['annually_price'],
                'price_type' => self::$chargeTypeAnnually,
                'price_type_remark' =>  '包年',
                'charge_year' => $user_finance_data['year'],
                'charge_year_start' => $annulYears[0],
                'charge_year_end' => end($annulYears),
            ]
        );

        //如果配置的单年度不按包年计算
        if(!$finance_config['single_year_charge_as_annual']){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'single_year_charge_as_annual  .',
                    'year' => $user_finance_data['year'],
                    '$annulYears' => $annulYears,
                    'upload_data_id' => $id,
                    '$uploadId' =>$uploadId,
                    'single_year_charge_as_annual' => $finance_config['single_year_charge_as_annual'],
                ])
            );
            // 数据不连续(有的数据缺失了) ： 改包年为单年
            if(
                !AdminUserFinanceData::checkIfAllYearsDataIsValid(
                    $user_finance_data['user_id'],
                    $user_finance_data['entName'],
                    $user_finance_data['year']
                )
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'some data is empty . charge by single year ',
                        'year' => $user_finance_data['year'],
                        '$annulYears' => $annulYears,
                        'upload_data_id' => $id,
                        '$uploadId' =>$uploadId,
                        'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                    ])
                );
                self::updatePriceType(
                    [
                        'id' => $id,
                        'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                        'price_type' => self::$chargeTypeByYear,
                        'price_type_remark' =>   '虽然是包年，但有缺数据的年份,按单年算',
                        'charge_year' => $user_finance_data['year'],
                        'charge_year_start' => '',
                        'charge_year_end' => '',
                    ]
                );
            }

            // 选择的不是全部包年年份 ：改为单年
            $checkIfArrayEquals = self::checkIfArrayEquals($selectYears,$annulYears);
            if(
                !$checkIfArrayEquals
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'select years not equal annul year . charge by single year ',
                        'year' => $user_finance_data['year'],
                        '$selectYears' => $selectYears,
                        '$annulYears' => $annulYears,
                        'upload_data_id' => $id,
                        '$uploadId' =>$uploadId,
                        'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                    ])
                );
                self::updatePriceType(
                    [
                        'id' => $id,
                        'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                        'price_type' => self::$chargeTypeByYear,
                        'price_type_remark' =>   '虽然是包年，但选的不是全部的包年年度,按单年算',
                        'charge_year' => $user_finance_data['year'],
                        'charge_year_start' => '',
                        'charge_year_end' => '',
                    ]
                );
            }
        }
        return true;
    }

    static  function  getYearPriceByConfig($year,$configArr){

        foreach (json_decode($configArr['normal_years_price_json'],true) as $configItem){
            if(
                $configItem['year'] == $year
            ){
                return $configItem['price'];
            };
        }
        return  CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'getYearPriceByConfig false',

                ]
            )
        );
    }

    static  function  checkIfArrayEquals($array1,$array2){
        sort($array1);
        sort($array2);

        if(
            count($array1) != count($array2)
        ){

            return  false;
        }

        foreach ($array1 as $key => $value){
            if(
                $array2[$key] != $value
            ){

                return  false;
            }
        }
        return  true;
    }

}
