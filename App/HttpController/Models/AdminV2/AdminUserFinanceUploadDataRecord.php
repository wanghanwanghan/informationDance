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
    
    static $stateInit = 1;
    static $stateInitCname = '初始化';
    static $stateHasCalculatePrice = 5;
    static $stateHasCalculatePriceCname = '已计算价格';
    static $stateExported = 10;

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
                        `user_finance_data_ids` in $ids  AND
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

    public static function calcluteRealPrice(
        $id,$financeConfigArr
    ){
        CommonService::getInstance()->log4PHP(
            'calcluteRealPrice start  '.$id. '  conf '.json_encode($financeConfigArr)
        );

        $info = AdminUserFinanceUploadDataRecord::findById($id);
        $dataArr = $info->toArray();
        $AdminUserFinanceData = AdminUserFinanceData::findById($dataArr['user_finance_data_id'])->toArray();
        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = AdminUserFinanceData::getChagrgeDetailsAnnually(
            $AdminUserFinanceData['year'],
            $financeConfigArr,
            $AdminUserFinanceData['user_id'],
            $AdminUserFinanceData['entName']
        ) ;

        CommonService::getInstance()->log4PHP(
            '包年    '.$id.' '.   '  conf '.json_encode($chagrgeDetailsAnnuallyRes)
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
                        ''
                    );
                    if(!$updateRes){
                        return CommonService::getInstance()->log4PHP(
                            'calculateRealPrice err1  update price error  IsAnnually '.$id
                        );
                    }
                }
            }
        }

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
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
                ''
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculateRealPrice err1  update price error  IsAnnually '.$id
                );
            }
        }

        return true;
    }


    public static function updateRealPrice($id,$real_price,$real_price_remark){
        $info = AdminUserFinanceUploadDataRecord::create()
            ->where('id',$id)
            ->get();

        return $info->update([
            'id' => $id,
            'real_price' => $real_price,
            'real_price_reamrk' => $real_price_remark,
        ]);
    }

}
