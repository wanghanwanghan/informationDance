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
    static $stateInitCname =  '文件待解析';
    static $stateParsed = 5;
    static $stateParsedCname =  '计算价格中';
    static $stateCalCulatedPrice = 10;
    static $stateCalCulatedPriceCname = '待导出';

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

    public static function getStatusMaps(){
        return [
            self::$stateInit => self::$stateInitCname,
            self::$stateParsed => self::$stateParsedCname,
            self::$stateCalCulatedPrice => self::$stateCalCulatedPriceCname,
            self::$stateHasGetData => self::$stateHasGetDataCname,
            self::$stateCalCulatedPrice2 => self::$stateCalCulatedPrice2Cname,
        ];

    }
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
    public static function pullFinanceDataById($upload_record_id){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'upload record pullFinanceDataById  strat',
                '$upload_record_id'=>$upload_record_id
            ])
        );
        $uploadRes = AdminUserFinanceUploadRecord::findById($upload_record_id)->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );
        foreach ($uploadDatas as $uploadData){
            $pullFinanceDataRes = AdminUserFinanceData::pullFinanceData(
                $uploadData['user_finance_data_id'],AdminUserFinanceUploadRecord::getFinanceConfigArray($upload_record_id)
            );
        }
        return true;
    }


    public static function findByIdAndFileName($user_id,$file_name){
        $res =  AdminUserFinanceUploadRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,   
            // 'status' => 1,  
        ])->get(); 

        return $res;
    }


    public static function checkByStatus($id,$status){
        $res =  self::findById($id);
        $res2 = ($res->getAttr('status')==$status)?true:false;
        CommonService::getInstance()->log4PHP(
            json_encode([
                'upload record checkByStatus   '=> 'start',
                'params $id' => $id,
                'params $status' => $status,
                '$res2'=>$res2,
            ])
        );
        return $res2;
    }

    public static function findByConditionV3($whereArr,$page){
        $model = AdminUserFinanceUploadRecord::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page){

        $model = AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->page($page)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }
    public static function findByCondition($whereArr,$offset, $limit){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->limit($offset, $limit)
            ->all();  
        return $res;
    }

    public static function changeStatus($id,$status){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'changeStatus start  ',
                '$id'=>$id,
                '$status' =>$status,
            ])
        );
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                'Upload record   findById '=> 'start',
                '$id'=>$id,
                '$res' =>$res
            ])
        );
        return $res;
    }

    public static function getAllFinanceDataByUploadRecordIdV2(
        $userId,$uploadRecordId
    ){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'getAllFinanceDataByUploadRecordIdV2   '=> 'start',
                $userId,$uploadRecordId
            ])
        );
        $allowedFields = AdminUserFinanceUploadRecord::getAllowedFieldArray($uploadRecordId);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$allowedFields   '=> $allowedFields
            ])
        );
        $AdminUserFinanceUploadDataRecords = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $userId,$uploadRecordId
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '$AdminUserFinanceUploadDataRecords   '=> $AdminUserFinanceUploadDataRecords,
                $userId,$uploadRecordId,$allowedFields
            ])
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
            $NewFinanceData2 = self::resetArray($NewFinanceData,$allowedFields);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'resetArray   '=> $NewFinanceData2
                ])
            );
            $returnDatas['export_data'][$NewFinanceData['id']] =  $NewFinanceData2;
            $NewFinanceData['UploadDataRecordId'] = $AdminUserFinanceUploadDataRecord['id'];
            $returnDatas['details'][$NewFinanceData['id']] =  $NewFinanceData;

        }

        return $returnDatas;
    }

    static function  resetArray($rawArray,$allowedField){
        $returnArr = [];
        foreach ($rawArray as $field => $value){
            if(
                in_array($field,$allowedField)
            ){
                $returnArr[$field] = $value;
            }
        }
        return $returnArr;
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                'setTouchTime start  ',
                '$id'=>$id,
                '$touchTime' =>$touchTime,
            ])
        );
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'upload record admin_user_finance_upload_record   findBySql ',
                '$where' => $where,
            ])
        );
        $Sql = "select * from    `admin_user_finance_upload_record`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    static  function  calAndSetMoney($uploadId){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceUploadRecord calAndSetMoney ',
                '$uploadId' => $uploadId
            ])
        );
        return self::updateMoneyById(
            $uploadId,
            self::calMoney($uploadId)['total_price']
        );
    }


    public static function updateLastChargeDate(
        $id,$last_charge_date
    ){
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'AdminUserFinanceUploadRecord updateLastChargeDate ',
                    [
                        'id' => $id,
                        '$last_charge_date' => $last_charge_date,
                    ]
                ]
            )
        );
        $info = self::findById($id);
        return $info->update([
            'id' => $id,
            'last_charge_date' => $last_charge_date,
        ]);
    }

    public static function ifHasChargeBefore(
        $id
    ){
        $info = self::findById($id);
        $res = false;
        if($info->getAttr('last_charge_date')>0){
            $res = true;
        }
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'AdminUserFinanceUploadRecord ifHasChargeBefore ',
                    [
                        'id' => $id,
                        'last_charge_date' => $info->getAttr('last_charge_date'),
                        '$res'=>$res,
                    ]
                ]
            )
        );
        return $res;
    }

    public static function ifCanDownload(
        $id
    ){
        $info = self::findById($id);
        $res = true;
        $last_charge_date = $info->getAttr('last_charge_date');


        if(
            $last_charge_date > 0 &&
            strtotime($last_charge_date) -time() >= 60*60*24
        ){
            $res = false;
        }
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'AdminUserFinanceUploadRecord ifCanDownload ',
                    [
                        'id' => $id,
                        '$last_charge_date' => $last_charge_date,
                        '$res'=>$res,
                    ]
                ]
            )
        );
        return $res;
    }

    public static function updateMoneyById(
        $id,$money
    ){
        CommonService::getInstance()->log4PHP(
           json_encode(
               [
                   'updateMoneyById ',
                   [
                       'id' => $id,
                       'money' => $money,
                   ]
               ]
           )
        );
        $info = self::findById($id);
        return $info->update([
            'id' => $id,
            'money' => $money,
        ]);
    }
    static function  calMoney($uploadId){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceUploadRecord calMoney ',
                '$uploadId' => $uploadId
            ])
        );
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
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_start']
                        == $uploadData['charge_year_start'] &&
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_end']
                        == $uploadData['charge_year_end']
                ){
                    $hasChargeBefore = true;
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  has cal 1',
                            'user_id' => $uploadData['user_id'],
                            'user_id chargeTypeAnnually ' => $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']]
                        ])
                    );
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
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  has cal 2',
                            'user_id' => $uploadData['user_id'],
                            'cache_end_date ' => $user_finance_data['cache_end_date']
                        ])
                    );
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
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  needs charge',
                            'user_id' => $uploadData['user_id'],
                            'entName' => $user_finance_data['entName'],
                            'price ' => $uploadData['price'],
                            'total_price ' => $chargeDetails['total_price'],
                        ])
                    );
                }
            }

            //按单年计费
            if(
                $uploadData['price_type'] == AdminUserFinanceUploadDataRecord::$chargeTypeByYear
            ){
                //本次里已经计算过
                if(
                    $chargeDetails['chargeTypeByYear'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year']
                        == $uploadData['charge_year']
                ){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  has cal 3',
                            'user_id' => $uploadData['user_id'],
                            'user_id chargeTypeByYear ' => $chargeDetails['chargeTypeByYear'][$uploadData['user_id']]
                        ])
                    );
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
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  has cal 4',
                            'user_id' => $uploadData['user_id'],
                            'cache_end_date  ' => $user_finance_data['cache_end_date']
                        ])
                    );
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
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'AdminUserFinanceUploadRecord calMoney  needs charge',
                            'user_id' => $uploadData['user_id'],
                            'entName' => $user_finance_data['entName'],
                            'price ' => $uploadData['price'],
                            'total_price ' => $chargeDetails['total_price'],
                        ])
                    );
                }
            }
        }
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'uplaod record calMoney ',
                    $uploadId,
                    $chargeDetails
                ]
            )
        );
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
        $finance_config =  json_decode($uploadRes['finance_config'],true);
        CommonService::getInstance()->log4PHP(
            json_encode([
                'upload record getFinanceConfigArray   '=> 'start ',
                '$finance_config' =>$finance_config,
            ])
        );
        return $finance_config;
    }

    static function  getAllowedFieldArray($uploadId){
        $finance_config = self::getFinanceConfigArray($uploadId);
        $arr = json_decode($finance_config['allowed_fields'],true);
        array_unshift($arr, 'year');
        array_unshift($arr, 'entName');

        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'getAllowedFieldArray ',
                    $arr
                ]
            )
        );
        return $arr;
    }


}
