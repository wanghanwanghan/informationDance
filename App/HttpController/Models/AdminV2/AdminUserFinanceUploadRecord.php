<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceUploadRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_upload_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateInitCname =  '初始';
    static $stateParsed = 5;
    static $stateParsedCname =  '已经解析入库';
    static $stateCalCulatedPrice = 10;
    static $stateCalCulatedPriceCname = '已经计算价格';

//    static $stateHasCheckBalanceOK = 13;
//    static $stateHasCheckBalanceOKCname = '已经检测完余额充足';
//
//    static $stateHasCheckBalanceNo = 16;
//    static $stateHasCheckBalanceNoCname = '已经检测完余额不足';
//
//    static $stateHasDisabledTemp = 18;
//    static $stateHasDisabledTempCname = '账户被临时关闭，暂时不允许拉取数据';
//
//    static $stateHasDisabledForever = 19;
//    static $stateHasDisabledForeverCname = '账户被永久关闭，暂时不允许拉取数据';

    static $stateHasGetData = 20;
    static $stateHasGetDataCname = '已取完数据';

    static $stateCalCulatedPrice2 = 25;
    static $stateCalCulatedPrice2Cname = '已经重新计算价格';

    static $stateHasCalcluteRealPrice = 25;
    static $stateHasCalcluteRealPriceCname = '已经计算完真实价格';

    static $stateHasCalclutePriceType = 30;
    static $stateHasCalclutePriceTypeCname = '已经计算完价格类型';


    public static function addUploadRecord($requestData){ 
        try {
           $res =  AdminUserFinanceUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'years' => $requestData['years'], 
                'file_path' => $requestData['file_path'],  
                'file_name' => $requestData['file_name'],  
                'title' => $requestData['title']?:'',
                'finance_config' => $requestData['finance_config'],  
                'readable_price_config' => $requestData['readable_price_config'],  
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadRecord addUploadRecord sql err',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByIdAndFileName($user_id,$file_name){
        $res =  AdminUserFinanceUploadRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,   
            // 'status' => 1,  
        ])->get(); 

        return $res;
    }

    public static function findByCondition($whereArr,$offset, $limit){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->limit($offset, $limit)
            ->all();  
        return $res;
    }

    public static function changeStatus($id,$status){ 
        $info = AdminUserFinanceUploadRecord::create()->where('id',$id)->get(); 
        return $info->update([
            'id' => $id,
            'status' => $status, 
        ]);
    }


    public static function findById($id){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function getAllFinanceDataByUploadRecordIdV2(
        $userId,$uploadRecordId
    ){
        $allowedFields = AdminUserFinanceUploadRecord::getAllowedFieldArray($uploadRecordId);
        $AdminUserFinanceUploadDataRecords = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $userId,$uploadRecordId,$allowedFields
        );
        $returnDatas  = [

        ];

        foreach ($AdminUserFinanceUploadDataRecords as $AdminUserFinanceUploadDataRecord){
            if($AdminUserFinanceUploadDataRecord['user_finance_data_id'] <= 0){
                continue;
            }
            $AdminUserFinanceData = AdminUserFinanceData::findById(
                $AdminUserFinanceUploadDataRecord['user_finance_data_id']
            )->toArray();

            if($AdminUserFinanceData['finance_data_id'] <= 0){
                continue;
            }

            $NewFinanceData = NewFinanceData::findById(
                $AdminUserFinanceData['finance_data_id']
            )->toArray();
            $returnDatas['export_data'][$NewFinanceData['id']] =  $NewFinanceData;
            $NewFinanceData['UploadDataRecordId'] = $AdminUserFinanceUploadDataRecord['id'];
            $returnDatas['details'][$NewFinanceData['id']] =  $NewFinanceData;

        }

        return $returnDatas;
    }


    public static function getFinanceCompleData($user_finance_data_id){ 
        //该数据对应的相关价格配置/缓存配置等
         $AdminUserFinanceDataRes = AdminUserFinanceData::findById(
            $user_finance_data_id
        );
        $AdminUserFinanceData = $AdminUserFinanceDataRes->toArray(); 

        //取实际的财务数据
        $NewFinanceDataRes = NewFinanceData::findById($AdminUserFinanceData['finance_data_id']);  
        $NewFinanceData = $NewFinanceDataRes->toArray();
        $NewFinanceData['user_finance_data_id'] = $user_finance_data_id;

        $price = $AdminUserFinanceDataRes['price'];
        $priceDetail = '';

        if(
            $AdminUserFinanceDataRes['cache_end_date'] > 0 &&
            strtotime($AdminUserFinanceDataRes['cache_end_date']) > time()
        ){
            $price = 0;
            $priceDetail = '在缓存期('.$AdminUserFinanceDataRes['cache_end_date'].')，不收费';
        }
        return [
            'finance_data' => $NewFinanceData,
            'price' => $price,
            'price_detail' => $priceDetail,
        ];
    }


    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select * from    `admin_user_finance_upload_record`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        return $data;
    }

    static  function  calAndSetMoney($uploadId){
        return self::updateMoneyById(
            $uploadId,
            self::calMoney($uploadId)
        );
    }
    public static function updateMoneyById(
        $id,$money
    ){
        $info = self::findById($id);
        return $info->update([
            'id' => $id,
            'money' => $money,
        ]);
    }
    static function  calMoney($uploadId){
        $uploadInfo = self::findById($uploadId)->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadInfo['user_id'],$uploadInfo['id']
        );
        $chargeDetails = [];

        foreach ($uploadDatas as $uploadData){
            $user_finance_data = AdminUserFinanceData::findById($uploadData['user_finance_data_id'])->toArray();
            //之前是否扣费过
            $hasChargeBefore = false;

            //包年计费
            if(
                 $uploadData['price_type'] == AdminUserFinanceUploadDataRecord::$chargeTypeAnnually
            ){
                //本次里已经计算过
                if(
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_start'] = $uploadData['charge_year_start'] &&
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_end'] = $uploadData['charge_year_end']
                ){
                    $hasChargeBefore = true;
                };

                //之前已经收费过
                if(
                    $user_finance_data['cache_end_date'] >= date('Y-m-d H:i:s')
//                    AdminUserFinanceChargeInfo::ifChargedBeforeV2(
//                        $uploadData['user_id'],
//                        $user_finance_data['entName'],
//                        $uploadData['charge_year_start'],
//                        $uploadData['charge_year_end']
//                    )
                ){
                    $hasChargeBefore = true;
                };

                // 如果之前没计费过
                if(!$hasChargeBefore){
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']] =
                        [
                            'charge_year_start' => $uploadData['charge_year_start'],
                            'charge_year_end' => $uploadData['charge_year_end'],
                            'price' => $uploadData['price'],
                        ];
                    $chargeDetails['total_price'] += $uploadData['price'];
                }
            }

            //按单年计费
            if(
                $uploadData['price_type'] == AdminUserFinanceUploadDataRecord::$chargeTypeByYear
            ){
                //本次里已经计算过
                if(
                    $chargeDetails['chargeTypeByYear'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year'] = $uploadData['charge_year']
                ){
                    $hasChargeBefore = true;
                };
                //之前已经收费过
                if(
                    $user_finance_data['cache_end_date'] >= date('Y-m-d H:i:s')
//                    AdminUserFinanceChargeInfo::ifChargedBefore(
//                            $uploadData['user_id'],
//                            $user_finance_data['entName'],
//                            $uploadData['charge_year']
//                        )
                ){
                    $hasChargeBefore = true;
                };

                // 如果之前没计费过
                if(!$hasChargeBefore){
                    $chargeDetails['chargeTypeByYear'][$uploadData['user_id']][$user_finance_data['entName']] =
                        [
                            'charge_year' => $uploadData['charge_year'],
                            'price' => $uploadData['price'],
                        ];
                    $chargeDetails['total_price'] += $uploadData['price'];
                }
            }
        }
        return $chargeDetails;
    }

    static function  chargeMoney($uploadId){
        $uploadInfo = self::findById($uploadId)->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadInfo['user_id'],$uploadInfo['id']
        );
        $chargeDetails = [];

        foreach ($uploadDatas as $uploadData){
            $user_finance_data = AdminUserFinanceData::findById($uploadData['user_finance_data_id'])->toArray();
            //之前是否扣费过
            $hasChargeBefore = false;

            //包年计费
            if(
                $uploadData['price_type'] == AdminUserFinanceUploadDataRecord::$chargeTypeAnnually
            ){
                //本次里已经计算过
                if(
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_start'] = $uploadData['charge_year_start'] &&
                        $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_end'] = $uploadData['charge_year_end']
                ){
                    $hasChargeBefore = true;
                };

                //之前已经收费过
                if(
                    AdminUserFinanceChargeInfo::ifChargedBeforeV2(
                        $uploadData['user_id'],
                        $user_finance_data['entName'],
                        $uploadData['charge_year_start'],
                        $uploadData['charge_year_end']
                    )
                ){
                    $hasChargeBefore = true;
                };

                // 如果之前没计费过
                if(!$hasChargeBefore){
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']] =
                        [
                            'charge_year_start' => $uploadData['charge_year_start'],
                            'charge_year_end' => $uploadData['charge_year_end'],
                            'price' => $uploadData['price'],
                        ];
                    $chargeDetails['total_price'] += $uploadData['price'];
                }
            }

            //按单年计费
            if(
                $uploadData['price_type'] == AdminUserFinanceUploadDataRecord::$chargeTypeByYear
            ){
                //本次里已经计算过
                if(
                    $chargeDetails['chargeTypeByYear'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year'] = $uploadData['charge_year']
                ){
                    $hasChargeBefore = true;
                };
                //之前已经收费过
                if(
                    AdminUserFinanceChargeInfo::ifChargedBefore(
                        $uploadData['user_id'],
                        $user_finance_data['entName'],
                        $uploadData['charge_year']
                    )
                ){
                    $hasChargeBefore = true;
                };

                // 如果之前没计费过
                if(!$hasChargeBefore){
                    $chargeDetails['chargeTypeByYear'][$uploadData['user_id']][$user_finance_data['entName']] =
                        [
                            'charge_year' => $uploadData['charge_year'],
                            'price' => $uploadData['price'],
                        ];
                    $chargeDetails['total_price'] += $uploadData['price'];
                }
            }
        }
        return $chargeDetails;
    }

    static function getFinanceConfigArray($uploadId){
        $uploadRes = self::findById($uploadId)->toArray();
        return json_decode($uploadRes['finance_config'],true);
    }

    static function  getAllowedFieldArray($uploadId){
        $finance_config = self::getFinanceConfigArray($uploadId);
        return json_decode($finance_config['allowed_fields'],true);
    }

}
