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
        $returnDatas  = [];

        $AdminUserFinanceUploadDataRecords = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $userId,$uploadRecordId,$status,[]
        );
        foreach ($AdminUserFinanceUploadDataRecords as $AdminUserFinanceUploadDataRecord){
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
            $returnDatas['charge_details'][$NewFinanceData['id']] = $AdminUserFinanceUploadDataRecord['real_price'];
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
 

}
