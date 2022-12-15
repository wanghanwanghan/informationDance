<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
 

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_export_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateParsed = 5;
    static $stateExported = 10;

    public static function addExportRecord($requestData){
        try {
           $res =  AdminUserFinanceExportRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'price' => $requestData['price'],  
                'total_company_nums' => $requestData['total_company_nums'],  
                'config_json' => $requestData['config_json'],  
                'upload_record_id' => $requestData['upload_record_id'],
               'path' => $requestData['path']?:'',
               'file_name' => $requestData['file_name']?:'',
               'reamrk' => $requestData['reamrk'],
                'status' => $requestData['status'],
                'queue_id' => $requestData['queue_id'],
                'batch' => $requestData['batch'],
           ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .' false',
                    '$requestData' =>$requestData,
                    'message' => $e->getMessage()
                ])
            );
        }  

        return $res;
    }

    public static function findByConditionV3($whereArr,$page,$pageSize){
        $model = AdminUserFinanceExportRecord::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$pageSize)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV4($whereArr){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportRecord  findByConditionV4   ',
                '$whereArr' => $whereArr,
            ])
        );

        $model = AdminUserFinanceExportRecord::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        return $model ->all();
    }

    static function getYieldDataToExport($whereArr){
        //每次取十条
        $size = 10 ;
        $model = AdminUserFinanceExportRecord::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate'])->limit($size);
        }
        $res = $model ->all();

        $datas = [];

        //每次取十条  直到取完
        while (!empty($res)) {
            $lastId =  0;
            foreach ($res as $dateItem){
                $lastId = $dateItem['id'];
                yield $datas[] =  [
                    $dateItem['id'],
                    date('Y-m-d H:i:s',$dateItem['created_at']),
                    $dateItem['file_name'],
                    $dateItem['price'],
                ];
            }

            $model = AdminUserFinanceExportRecord::create();
            foreach ($whereArr as $whereItem){
                $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate'])->limit($size);
            }

            $model->where('id', $lastId, '>')->limit($size);
            $res = $model ->all();
        }
    }

    public static function findById($id){
        $res =  AdminUserFinanceExportRecord::create()
            ->where('id',$id)
            ->get();
        return $res;
    }
    public static function setFilePath($id,$path,$fileName){

        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportRecord  setFilePath   '=>$id,$path,$fileName
            ])
        );
        $info = self::findById($id);

        return $info->update([
            'path' => $path,
            'file_name' => $fileName,
        ]);
    }

    public static function findByIdAndFileName($user_id,$file_name){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'user_id' => $user_id,  
            'file_name' => $file_name,
        ])->get(); 

        return $res;
    }

    public static function findByBatchNo($user_id,$batch){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'user_id' => $user_id,
            'batch' => $batch,
        ])->get();

        return $res;
    }

    public static function findByQueue($queue_id){
        $res =  AdminUserFinanceExportRecord::create()->where([
//            'user_id' => $user_id,
            'queue_id' => $queue_id,
            // 'status' => 1,
        ])->get();

        return $res;
    }

    public static function findByBatch($batch){

        $res =  AdminUserFinanceExportRecord::create()->where([
            'batch' => $batch,
        ])->get();

        return $res;
    }

    public static function findByQueueAndUploadId($queue_id,$upload_record_id){
        $res =  AdminUserFinanceExportRecord::create()->where([
            'upload_record_id' => $upload_record_id,
            'queue_id' => $queue_id,
        ])->get();

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }



    public  function findAllByUserId($userId){
        $res =  AdminUserFinanceExportRecord::create()
            ->where([
                'user_id' =>$userId
            ])
            ->all();
        return $res;
    }


    static  function  addRecordV2($dataItem){
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'export record start ' ,
//                '$dataItem' =>$dataItem
//            ])
//        );

        $exportRes = AdminUserFinanceExportRecord::findByBatch($dataItem['queue_id']);
        if($exportRes){
            return $exportRes->getAttr('id');
        }

        $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addExportRecord(
            $dataItem
        );
        if(
            $AdminUserFinanceExportRecordId <= 0
        ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    '设置导出记录' ,
                    '$AdminUserFinanceExportRecordId' =>$AdminUserFinanceExportRecordId,
                    '$dataItem' =>$dataItem
                ])
            );
        }
        return  $AdminUserFinanceExportRecordId;
    }
}
