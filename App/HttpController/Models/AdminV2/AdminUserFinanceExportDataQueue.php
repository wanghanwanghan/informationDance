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

    static  $state_needs_confirm = 10;
    static  $state_needs_confirm_cname =  '需要确认';

    static  $state_confirmed = 20;
    static  $state_confirmed_cname =  '已确认';

    static  $state_succeed = 30;
    static  $state_succeed_cname =  '下载成功';


    public static function setFinanceDataState($queueId){
        $queueData = self::findById($queueId)->toArray();
        $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();

        CommonService::getInstance()->log4PHP(
            json_encode([
                'setFinanceDataState  strat',
                '$queueId'=>$queueId
            ])
        );

        $uploadDatas = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
            $uploadRes['user_id'],$uploadRes['id']
        );

        $status = self::$state_confirmed;
        foreach ($uploadDatas as $uploadData){
            if(
                AdminUserFinanceData::checkDataNeedConfirm($uploadData['user_finance_data_id'])
            ){
                $status = self::$state_needs_confirm;
                break;
            };
        }
        return self::updateStatusById($queueId,$status);

    }

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
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportDataQueue  addRecordV2' ,
                " WHERE upload_record_id = ".$info['upload_record_id'].
                "  AND status = ".AdminUserFinanceExportDataQueue::$state_init,
                $info
            ])
        );

        if(
            self::findByBatch($info['batch'])
//            AdminUserFinanceExportDataQueue::findBySql(
//                " WHERE upload_record_id = ".$info['upload_record_id'].
//                "  AND status = ".AdminUserFinanceExportDataQueue::$state_init
//            )
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  addRecordV2 exists' ,
                    " WHERE upload_record_id = ".$info['upload_record_id'].
                    "  AND status = ".AdminUserFinanceExportDataQueue::$state_init,
                    $info
                ])
            );
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
               'user_id' => $requestData['user_id'],
               'path' => $requestData['path']?:'',
               'file_name' => $requestData['file_name']?:'',
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

    public static function findByBatch($batch){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('batch',$batch)
            ->get();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceExportDataQueue::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function setFilePath($id,$path,$fileName){

        CommonService::getInstance()->log4PHP(
            json_encode([
                'setFilePath   '=>$id,$path,$fileName
            ])
        );
        $info = AdminUserFinanceExportDataQueue::findById($id);

        return $info->update([
            'path' => $path,
            'file_name' => $fileName,
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
