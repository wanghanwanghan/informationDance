<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;


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
    static $stateCalCulatedPriceCname = '待组装数据';

    static $stateTooManyPulls = 12;
    static $stateTooManyPullsCname = '每日剩余拉取次数不足';

    static $stateBalanceNotEnough = 15;
    static $stateBalanceNotEnoughCname = '余额不足';

    static $stateNeedsConfirm = 20;
    static $stateNeedsConfirmCname = '用户确认中';
    static $stateConfirmed = 25;
    static $stateConfirmedCname = '数据组装完毕，待导出';

    public static function getStatusMaps(){

        return [
            self::$stateInit => self::$stateInitCname,
            self::$stateParsed => self::$stateParsedCname,
            self::$stateCalCulatedPrice => self::$stateCalCulatedPriceCname,
            self::$stateTooManyPulls => self::$stateTooManyPullsCname,
            self::$stateBalanceNotEnough => self::$stateBalanceNotEnoughCname,
            self::$stateNeedsConfirm => self::$stateNeedsConfirmCname,
            self::$stateConfirmed => self::$stateConfirmedCname,
        ];

    }
    public static function addUploadRecord($requestData){
        $dbData = [
            'user_id' => $requestData['user_id'],
            'years' => $requestData['years'],
            'batch' => $requestData['batch'],
            'file_path' => $requestData['file_path'],
            'file_name' => $requestData['file_name'],
            'title' => $requestData['title']?:'',
            'finance_config' => $requestData['finance_config'],
            'readable_price_config' => $requestData['readable_price_config'],
            'reamrk' => $requestData['reamrk']?:'',
            'status' => $requestData['status']?:1,
        ];
        try {
           $res =  AdminUserFinanceUploadRecord::create()->data($dbData)->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .' false',
                    '$requestData' => $requestData,
                    '$dbData' => $dbData,
                    'return getMessage' =>  $e->getMessage(),
                ])
            );  
        }

        return $res;
    }
    public static function pullFinanceDataById($upload_record_id){
        $uploadRes = AdminUserFinanceUploadRecord::findById($upload_record_id)->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );
        foreach ($uploadDatas as $uploadData){
            $pullFinanceDataRes = AdminUserFinanceData::pullFinanceData(
                $uploadData['user_finance_data_id'],AdminUserFinanceUploadRecord::getFinanceConfigArray($upload_record_id)
            );
            if(!$pullFinanceDataRes){
                return  CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        '$pullFinanceDataRes failed',
                    ])
                );
            }
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

    public static function findByBatch($batch){
        $res =  AdminUserFinanceUploadRecord::create()->where([
            'batch' => $batch,
            // 'status' => 1,
        ])->get();
        return $res;
    }


    public static function checkByStatus($id,$status){
        $res =  self::findById($id);
        $res2 = ($res->getAttr('status')==$status)?true:false;
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
            $userId,$uploadRecordId
        );

        $returnDatas  = [

        ];

        foreach ($AdminUserFinanceUploadDataRecords as $AdminUserFinanceUploadDataRecord){
            if($AdminUserFinanceUploadDataRecord['user_finance_data_id'] <= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'finance_data_id < 0',
                        'user_finance_data_id continue ' => $AdminUserFinanceUploadDataRecord['user_finance_data_id']
                    ])
                );
                continue;
            }
            $AdminUserFinanceData = AdminUserFinanceData::findById(
                $AdminUserFinanceUploadDataRecord['user_finance_data_id']
            )->toArray();

            if($AdminUserFinanceData['finance_data_id'] <= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'finance_data_id < 0',
                        'finance_data_id' => $AdminUserFinanceData['finance_data_id']
                    ])
                );
                continue;
            }

            $NewFinanceData = NewFinanceData::findById(
                $AdminUserFinanceData['finance_data_id']
            )->toArray();
            $NewFinanceData2 = self::resetArray($NewFinanceData,$allowedFields);
            $returnDatas['export_data'][$NewFinanceData['id']] =  $NewFinanceData2;
            $NewFinanceData['UploadDataRecordId'] = $AdminUserFinanceUploadDataRecord['id'];
            $returnDatas['details'][$NewFinanceData['id']] =  $NewFinanceData;

        }

        return $returnDatas;
    }


    //yield 按行
    public static function getAllFinanceDataByUploadRecordIdV3(
        $userId,$uploadRecordId
    ){
        //允许的字段
        $allowedFields = AdminUserFinanceUploadRecord::getAllowedFieldArray($uploadRecordId);

        //类型 getType
        $dataType = AdminUserFinanceUploadRecord::getType($uploadRecordId);
        $AdminUserFinanceUploadDataRecords = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $userId,$uploadRecordId
        );

        $returnDatas  = [];

        //上传记录详情
        foreach ($AdminUserFinanceUploadDataRecords as $AdminUserFinanceUploadDataRecord){
            if($AdminUserFinanceUploadDataRecord['user_finance_data_id'] <= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'finance_data_id < 0',
                        'user_finance_data_id continue ' => $AdminUserFinanceUploadDataRecord['user_finance_data_id']
                    ])
                );
                continue;
            }

            //对应的财务数据信息
            $AdminUserFinanceData = AdminUserFinanceData::findById(
                $AdminUserFinanceUploadDataRecord['user_finance_data_id']
            )->toArray();

            if($AdminUserFinanceData['finance_data_id'] <= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'finance_data_id < 0',
                        'finance_data_id' => $AdminUserFinanceData['finance_data_id']
                    ])
                );
                continue;
            }

            //对应的实际财务数据
            $NewFinanceData = NewFinanceData::findById(
                $AdminUserFinanceData['finance_data_id']
            )->toArray();

            $NewFinanceData2 = self::resetArray($NewFinanceData,$allowedFields);

            //原始值
            if(AdminUserFinanceConfig::$type_yuanshi == $dataType){
                $NewFinanceData2 = AdminUserFinanceConfig::formatchYuanZhi($NewFinanceData2);
            }
            //字典
            if(AdminUserFinanceConfig::$type_zidian == $dataType){
                $NewFinanceData2 = AdminUserFinanceConfig::formatchZiDian($NewFinanceData2);
            }

            //区间
            if(AdminUserFinanceConfig::$type_qvjian == $dataType){
                $NewFinanceData2 = AdminUserFinanceConfig::formatchQvJian($NewFinanceData2);
            }

            yield $returnDatas[] = [
                'NewFinanceData' => $NewFinanceData2,
                'AdminUserFinanceUploadDataRecord'=>$AdminUserFinanceUploadDataRecord,
                'AdminUserFinanceData'=>$AdminUserFinanceData,
            ];

        }
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


    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function reducePriority($id,$nums){
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'priority' => $info->getAttr('priority')+$nums,
        ]);
    }

    public static function setData($id,$field,$value){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function checkIfNeedsConfirm($upload_record_id){
        $uploadRes = AdminUserFinanceUploadRecord::findById($upload_record_id)->toArray();
        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );

        $needs = false;
        foreach ($uploadDatas as $uploadData){
            if(
                AdminUserFinanceData::checkDataNeedConfirm($uploadData['user_finance_data_id'])
            ){
                $needs = true;
                break;
            };
        }

        return $needs;

    }

    //
    public static function findBySql($where){
        $Sql = "select * from    `admin_user_finance_upload_record`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        return $data;
    }

    static  function  calAndSetMoney($uploadId){

        return self::updateMoneyById(
            $uploadId,
            self::calMoney($uploadId)['total_price']
        );
    }


    public static function updateLastChargeDate(
        $id,$last_charge_date
    ){
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                '$id' => $id,
                '$last_charge_date' => $last_charge_date
            ])
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
            strtotime($last_charge_date) -time() >= 60*60* intval(self::getFinanceConfigArray($id)['cache'])
        ){
            $res = false;
        }

        return $res;
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

    //计算到底多少钱
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
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_start']
                        == $uploadData['charge_year_start'] &&
                    $chargeDetails['chargeTypeAnnually'][$uploadData['user_id']][$user_finance_data['entName']]['charge_year_end']
                        == $uploadData['charge_year_end']
                ){
                    $hasChargeBefore = true;
                };

                //之前已经收费过
                if(
                    $user_finance_data['cache_end_date'] >= date('Y-m-d H:i:s') ||
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
                    AdminUserFinanceUploadDataRecord::updateRealPrice(
                        $uploadData['id'],$uploadData['price'], $uploadData['charge_year_start'].'~'. $uploadData['charge_year_end'].'包年计费'
                    );
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ ,
                            'needs charge . ',
                            '$uploadDataId' => $uploadData['id'],
                            'chargeTypeAnnually' ,
                            'charge_year_start' => $uploadData['charge_year_start'],
                            'charge_year_end' => $uploadData['charge_year_end'],
                            'price' => $uploadData['price'],
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
                    $hasChargeBefore = true;
                };
                //之前已经收费过
                if(
                    $user_finance_data['cache_end_date'] >= date('Y-m-d H:i:s')   ||
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
                    AdminUserFinanceUploadDataRecord::updateRealPrice(
                        $uploadData['id'],$uploadData['price'], $uploadData['charge_year'].'单年计费'
                    );
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ ,
                            'needs charge . ',
                            '$uploadDataId' => $uploadData['id'],
                            'chargeTypeByYear' ,
                            'charge_year' => $uploadData['charge_year'],
                            'price' => $uploadData['price'],
                        ])
                    );
                }
            }
        }
        return $chargeDetails;
    }

    static function getFinanceConfigArray($uploadId){
        $uploadRes = self::findById($uploadId)->toArray();
        $finance_config =  json_decode($uploadRes['finance_config'],true);

        return $finance_config;
    }

    static function  getAllowedFieldArray($uploadId){
        $finance_config = self::getFinanceConfigArray($uploadId);
        $arr = json_decode($finance_config['allowed_fields'],true);
        array_unshift($arr, 'year');
        array_unshift($arr, 'entName');


        return $arr;
    }

    static function  getType($uploadId){
        $finance_config = self::getFinanceConfigArray($uploadId);

        return $finance_config['type'];
    }


}
