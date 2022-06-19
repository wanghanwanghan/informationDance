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
        try {
           $res =  AdminUserFinanceUploadDataRecord::create()->data([
                'user_id' => $requestData['user_id'],  
                'record_id' => $requestData['record_id'],  
                'user_finance_data_id' => $requestData['user_finance_data_id'],  
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadDataRecord sql err',
                    $e->getMessage(),
                ])
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
            return CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        ' RunDealFinanceCompanyData parseDataToDb  $AdminUserFinanceUploadDataRecordId is 0 faile'
                    ]
                )
            );
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
//                'status' => $status,
            ])
                ->all();
        }
        else{
            $res =  AdminUserFinanceUploadDataRecord::create()->where([
                'user_id' => $user_id,
                'record_id' => $record_id,
//                'status' => $status,
            ])
                ->field($fieldsArr)
                ->all();
        }

        return $res;
    }


    public static function findFinanceDataByUserIdAndRecordId(
        $user_id,$record_id,$status
    ){ 
        $res =  AdminUserFinanceUploadDataRecord::create()->where([
            'user_id' => $user_id,  
            'record_id' => $record_id,   
            'status' => $status,   
        ])->field(['id','user_finance_data_id'])
        ->all(); 

        $user_finance_data_ids = array_column($res,'user_finance_data_id');
        $dataRes =  AdminUserFinanceUploadDataRecord::create()->where(
            'id',$user_finance_data_ids,'IN'
        )->field(['id','user_finance_data_id'])
        ->all(); 

        return $dataRes;
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

    public static function checkIfHasCalclutePrice(
        $user_id,$record_id,$user_finance_data_ids
    ){
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'checkIfHasCalclutePrice    ',
                    '$user_id' =>$user_id,
                    '$record_id' =>$record_id,
                    '$user_finance_data_ids' =>$user_finance_data_ids,
                ]
            )
        );

        $ids = '("'.implode('","',$user_finance_data_ids).'")';
        $sql = " select id from  `admin_user_finance_upload_data_record`
                    WHERE
                        `user_finance_data_id` in $ids  AND
                        user_id = $user_id  AND
                        record_id = '$record_id' AND 
                        real_price >0 
                    LIMIT  1
                ";
        $validDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        CommonService::getInstance()->log4PHP(
            ' 是否之前计算过价格  $sql '. $sql
        );

        if(
            empty($validDatalist)
        ){
            return CommonService::getInstance()->log4PHP(
                ' 之前没算过价格  $sql '. $sql
            );
        }
        return true;
    }

    // $yearsArr 用户选的下载的年度
    public static function calcluteRealPrice(
        $id,$financeConfigArr,$yearsArr
    ){
        CommonService::getInstance()->log4PHP(
           json_encode(
               [
                   'calcluteRealPrice',
                   $id,$financeConfigArr,$yearsArr
               ]
           )
        );

        $info = AdminUserFinanceUploadDataRecord::findById($id);
        $dataArr = $info->toArray();
        $AdminUserFinanceData = AdminUserFinanceData::findById($dataArr['user_finance_data_id'])->toArray();
        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = AdminUserFinanceData::getChagrgeDetailsAnnually(
            $AdminUserFinanceData['year'],
            $financeConfigArr,
            $AdminUserFinanceData['user_id'],
            $AdminUserFinanceData['entName'],
            $yearsArr
        ) ;

        CommonService::getInstance()->log4PHP(
          json_encode(
              [
                  'calcluteRealPrice getChagrgeDetailsAnnually',
                  $AdminUserFinanceData['year'],
                  $financeConfigArr,
                  $AdminUserFinanceData['user_id'],
                  $AdminUserFinanceData['entName'],
                  $yearsArr
              ]
          )
        );
        // 是年度收费 需要看之前年度是否扣费过
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){
            // 有有效的数据
            if($chagrgeDetailsAnnuallyRes['ValidDataIds']){
                //查看之前是否计算过价格
                if(
                    self::checkIfHasCalclutePrice(
                        $dataArr['user_id'],
                        $dataArr['record_id'],
                        $chagrgeDetailsAnnuallyRes['ValidDataIds']
                    )
                ){
                    $updateRes = self::updateRealPrice(
                        $id,
                        $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                        json_encode(
                            [
                                'allDataIds' => $chagrgeDetailsAnnuallyRes['allDataIds']
                            ]
                        )
                    );
                    if(!$updateRes){
                        return CommonService::getInstance()->log4PHP(
                            'calculateRealPrice err1  update price error  IsAnnually '.$id
                        );
                    }
                }
            }
        }
        if($chagrgeDetailsAnnuallyRes['ChargeByYear']){

        }

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = AdminUserFinanceData::getChagrgeDetailsByYear(
            $AdminUserFinanceData['year'],
            $financeConfigArr,
            $AdminUserFinanceData['user_id'],
            $AdminUserFinanceData['entName']
        ) ;
        CommonService::getInstance()->log4PHP(
            '按年    '.$id.' '.'  conf '.json_encode($chagrgeDetailsByYearsRes)
        );
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){
            $updateRes = self::updateRealPrice(
                $id,
                $chagrgeDetailsAnnuallyRes['YearPrice'],
                json_encode(
                    [
                        'allDataIds' => $chagrgeDetailsAnnuallyRes['allDataIds']
                    ]
                )
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculateRealPrice err1  update price error  IsAnnually '.$id
                );
            }
        }
        return true;
    }

    public static function updatePriceType($info){
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'updatePriceType',
                    'id' => $info['id'],
                    'price' => $info['price'],
                    'price_type' => $info['price_type'],
                    'price_type_remark' => $info['price_type_remark'],
                    'charge_year' => $info['charge_year'],
                    'charge_year_start' => $info['charge_year_start'],
                    'charge_year_end' => $info['charge_year_end'],
                ]
            )
        );

        $info = AdminUserFinanceUploadDataRecord::create()
            ->where('id',$info['id'])
            ->get();

        $res = $info->update([
            'id' => $info['id'],
            'price' => $info['price'],
            'price_type' => $info['price_type'],
            'price_type_remark' => $info['price_type_remark'],
            'charge_year' => $info['charge_year'],
            'charge_year_start' => $info['charge_year_start'],
            'charge_year_end' => $info['charge_year_end'],
        ]);
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'updatePriceType res ',
                    $res, 
                ]
            )
        );
        return $res;
    }

    // $id,$recordId
    public static function updateChargeInfo($id,$uploadId){
        $uploadInfo = AdminUserFinanceUploadRecord::findById($uploadId);
        $uploadInfo = $uploadInfo->toArray();
        $dataInfo = self::findById($id);
        $dataInfo = $dataInfo->toArray();

        //用户的配置
        $finance_config = json_decode($uploadInfo['finance_config'],true);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'updateChargeInfo  $finance_config',
                $finance_config
            ])
        );
        //用户该次选择的年限
        $selectYears = json_decode($uploadInfo['years'],true);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'updateChargeInfo  $selectYears',
                $selectYears
            ])
        );
        //用户财务其他信息
        $user_finance_data = AdminUserFinanceData::findById($dataInfo['user_finance_data_id']);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'updateChargeInfo  $user_finance_data',
                $user_finance_data
            ])
        );
        //按包年计费？按年计费
        $annulYears = json_decode($finance_config['annually_years'],true);
        sort($annulYears);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'updateChargeInfo  $annulYears',
                $annulYears
            ])
        );

        //默认是包年
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

        if(
            !in_array($user_finance_data['year'],$annulYears)
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'updateChargeInfo  not in annulYears, charge by year ,',
                    $user_finance_data['year'],$annulYears,
                    [
                        'id' => $id,
                        'price' => self::getYearPriceByConfig($user_finance_data['year'],$finance_config),
                        'price_type' => self::$chargeTypeByYear,
                        'price_type_remark' =>  '不属包年年度,按单年计算',
                        'charge_year' => $user_finance_data['year'],
                        'charge_year_start' => '',
                        'charge_year_end' => '',
                    ]
                ])
            );
            self::updatePriceType(
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


        //如果配置的单年度 不按包年计算
        if(!$finance_config['single_year_charge_as_annual']){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'updateChargeInfo  single_year_charge_as_annual  0 ',
                    $finance_config['single_year_charge_as_annual']
                ])
            );

            // 数据不连续 ： 改包年为单年
            if(
                !AdminUserFinanceData::checkIfAllYearsDataIsValid(
                    $user_finance_data['user_id'],
                    $user_finance_data['entName'],
                    $user_finance_data['year']
                )
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'updateChargeInfo  checkIfAllYearsDataIsValid no ',
                        $user_finance_data['user_id'],
                        $user_finance_data['entName'],
                        $user_finance_data['year']
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
            if(
                !self::checkIfArrayEquals($selectYears,$annulYears)
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'updateChargeInfo  checkIfArrayEquals no ',
                        $selectYears,$annulYears
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
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'getYearPriceByConfig',
                    '$year'=>$year,'$configArr'=>$configArr,
                    'normal_years_price_json_arr' => json_decode($configArr['normal_years_price_json'],true)

                ]
            )
        );;

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
        );;
    }

    static  function  checkIfArrayEquals($array1,$array2){
        sort($array1);
        sort($array2);

        if(
            count($array1) != count($array2)
        ){
            return  CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'checkIfArrayEquals false',
                        $array1,$array2
                    ]
                )
            );
        }

        foreach ($array1 as $key => $value){
            if(
                $array2[$key] != $value
            ){
                return  CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'checkIfArrayEquals false',
                            $array1,$array2
                        ]
                    )
                );
            }
        }

        return  true;
    }

}
