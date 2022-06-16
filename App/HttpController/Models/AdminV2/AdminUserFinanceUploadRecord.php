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

    static $stateHasCalcluteRealPrice = 25;
    static $stateHasCalcluteRealPriceCname = '已经计算完真实价格';

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
                    'AdminUserFinanceUploadRecord sql err',
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
        $userId,$uploadRecordId,$status,$keepPrice = 1
    ){


        $AdminUserFinanceUploadDataRecords = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $userId,$uploadRecordId,$status,[]
        );
        $uploadRecordRes = self::findById($uploadRecordId);
        $finance_config_arr  = json_decode($uploadRecordRes->getAttr('finance_config'),true);
        $returnDatas  = [
            'config_arr' =>  $finance_config_arr
        ];

        foreach ($AdminUserFinanceUploadDataRecords as $AdminUserFinanceUploadDataRecord){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '$AdminUserFinanceUploadDataRecord',
                    $AdminUserFinanceUploadDataRecord,
                ])
            );
            if($AdminUserFinanceUploadDataRecord['user_finance_data_id'] <= 0){
                continue;
            }
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'user_finance_data_id',
                    $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                ])
            );
            $AdminUserFinanceData = AdminUserFinanceData::findById(
                $AdminUserFinanceUploadDataRecord['user_finance_data_id']
            )->toArray();

            if($AdminUserFinanceData['finance_data_id'] <= 0){
                continue;
            }
            // 财务数据id
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'finance_data_id',
                    $AdminUserFinanceData['finance_data_id'],
                ])
            );
            $NewFinanceData = NewFinanceData::findById(
                $AdminUserFinanceData['finance_data_id']
            )->toArray();
            $returnDatas['finance_data'][$NewFinanceData['id']] =  $NewFinanceData;
            // TODO  需要check下上次计费时间
            // TODO  需要指定导出字段
            $returnDatas['charge_details'][$NewFinanceData['id']] =
                [
                    'real_price' => $AdminUserFinanceUploadDataRecord['real_price'],
                    'real_price_remark' => json_decode($AdminUserFinanceUploadDataRecord['real_price_remark'],true),
                    'upload_data_id' => $AdminUserFinanceUploadDataRecord['id'],
                    'user_finance_data_id' => $AdminUserFinanceUploadDataRecord['user_finance_data_id']
                ]
            ;
            $returnDatas['total_charge'] += $AdminUserFinanceUploadDataRecord['real_price'];
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
        $Sql = " select *  
                            from  
                        `admin_user_finance_upload_record` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        return $data;
    }

}
