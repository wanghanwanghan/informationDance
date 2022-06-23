<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportDataQueue extends ModelBase
{

    protected $tableName = 'admin_user_finance_export_data_queue';

    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';

    static  $state_needs_confirm = 10;
    static  $state_needs_confirm_cname =  '用户确认数据中';

    static  $state_confirmed = 20;
    static  $state_confirmed_cname =  '文件生成中';

    static  $state_succeed = 30;
    static  $state_succeed_cname =  '文件生成成功';

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_needs_confirm => self::$state_needs_confirm_cname,
            self::$state_confirmed => self::$state_confirmed_cname,
            self::$state_succeed => self::$state_succeed_cname,
        ];

    }

    static  function  addRecordV2($info){

        if(
            self::findByBatch($info['batch'])
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  findByBatch ok ' ,
                    'params batch ' =>$info['batch']
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
               'real_charge' => $requestData['real_charge']?:0,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  addRecord  failed ' ,
                    'params $requestData ' =>$requestData,
                    'message' => $e->getMessage()
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

    public static function findByConditionV3($whereArr,$page){

        $model = AdminUserFinanceExportDataQueue::create();
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

    public static function findById($id){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByBatch($batch){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'findByBatch  start  ' ,
                'params $batch ' =>$batch
            ])
        );
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where('batch',$batch)
            ->get();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        CommonService::getInstance()->log4PHP(
            json_encode([
                ' AdminUserFinanceExportDataQueue  setTouchTime  '=>'start',
                '$id,' =>$id,
                '$touchTime' =>$touchTime,
            ])
        );
        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }



    public static function updateStatusById(
        $id,$status
    ){

        $info = AdminUserFinanceExportDataQueue::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'status' => $status,
            'updated_at'=>time()
        ]);
    }


    public static function setFilePath($id,$path,$fileName){

        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data queue setFilePath   '=>$id,$path,$fileName
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
        CommonService::getInstance()->log4PHP(
            json_encode([
                'Export data queue   '=> 'strat',
                '$where' =>$where,

            ])
        );
        $Sql = " select *  
                            from  
                        `admin_user_finance_export_data_queue` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
