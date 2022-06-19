<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportDataQueue extends ModelBase
{

    protected $tableName = 'admin_user_finance_export_data_queue';

    static  $state_init = 1;
    static  $state_init_cname =  '初始';


    static  $state_succeed = 10;
    static  $state_succeed_cname =  '成功';

    static  $state_failed = 20;
    static  $state_failed_cname =  '失败';

    public static function updateStatusById(
        $id,$status
    ){
        $info = AdminUserFinanceExportDataQueue::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'status' => $status,
        ]);
    }

    static  function  addRecordV2($info){
        if(
            AdminUserFinanceExportDataQueue::findBySql(
                " WHERE upload_record_id = ".$info['upload_record_id'].
                "  AND status = ".AdminUserFinanceExportDataQueue::$state_init
            )
        ){
            return  true;
        }

        return AdminUserFinanceExportDataQueue::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  AdminUserFinanceExportDataQueue::create()->data([
                'upload_record_id' => $requestData['upload_record_id'],
                'touch_time' => $requestData['touch_time'],
                'batch' => $requestData['batch'],
                'status' => $requestData['status']?:1,
           ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceData sql err',
                    $e->getMessage(),
                ])
            );  
        }  

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();
        return $res;
    }

    public static function findById($id){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceExportDataQueue::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `admin_user_finance_export_data_queue` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
