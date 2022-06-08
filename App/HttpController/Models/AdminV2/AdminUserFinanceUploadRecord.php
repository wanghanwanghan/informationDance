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
    
    static $stateInit = 0;
    static $stateInitCname =  '初始';
    static $stateParsed = 5;
    static $stateParsedCname =  '已经解析入库';
    static $stateCalCulatedPrice = 10;
    static $stateCalCulatedPriceCname = '已经计算价格'; 

    static $stateHasGetData = 20;
    static $stateHasGetDataCname = '已取完数据';

    static $stateHasSetCacheDate = 25;
    static $stateHasSetCacheDateCname = '已设置缓存期';


    public static function addUploadRecord($requestData){ 
        try {
           $res =  AdminUserFinanceUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'years' => $requestData['years'], 
                'file_path' => $requestData['file_path'],  
                'file_name' => $requestData['file_name'],  
                'title' => $requestData['title'],  
                'finance_config' => $requestData['finance_config'],  
                'readable_price_config' => $requestData['readable_price_config'],  
                'reamrk' => $requestData['reamrk'],  
                'status' => $requestData['status'],  
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'addCarInsuranceInfo Throwable continue',
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

    //获取财务数据 
    public static function getFinanceDataByUploadRecordId($uploadRecordId){
        // 节省内存 直接sql查询
        $sql = " SELECT
                    -- 上传的记录id
                    upload_data_record.record_id AS upload_id,
                    -- 用户财务信息id（单价/缓存期/上次收费时间等）
                    admin_finance.id AS admin_finance_info_id,
                    -- 计算出来的单价
                    admin_finance.price,
                    -- 上次收费时间
                    admin_finance.last_charge_date,
                    -- 缓存结束时间
                    admin_finance.cache_end_date,
                    -- 实际的财务数据
                    new_finance_data.* 
                FROM
                    -- 上传数据表
                    admin_user_finance_upload_data_record AS upload_data_record
                    -- 用户财务信息（单价/缓存期/上次收费时间等）
                    JOIN admin_user_finance_data AS admin_finance ON admin_finance.id = upload_data_record.user_finance_data_id
                    -- 实际财务数据表
                    JOIN new_finance_data AS finance_data ON finance_data.id = admin_finance.finance_data_id 
                WHERE
                    upload_data_record.record_id = $uploadRecordId
        ";

        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        
        return $list;
    }

}
