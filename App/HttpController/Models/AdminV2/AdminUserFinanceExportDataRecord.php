<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
 

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceExportDataRecord extends ModelBase
{
    protected $tableName = 'admin_user_finance_export_data_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateParsed = 5;
    static $stateExported = 10;


    static  function  addRecordV2($dataItem){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data record addRecordV2    '=> 'start',
                '$dataItem' =>$dataItem,
            ])
        );

        $AdminUserFinanceExportRecord =  AdminUserFinanceExportDataRecord::findByBatch($dataItem['batch']);
        if(
            $AdminUserFinanceExportRecord
        ){
            return $AdminUserFinanceExportRecord->getAttr('id');
        }

        return AdminUserFinanceExportDataRecord::addExportRecord(
            $dataItem
        );
    }


    public static function findByBatch($batch){

        $res =  AdminUserFinanceExportDataRecord::create()->where([
//            'user_id' => $user_id,
            'batch' => $batch,
            // 'status' => 1,
        ])->get();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data record findByBatch    '=> 'start',
                '$batch' =>$batch,
                '$res' =>$res,
            ])
        );
        return $res;
    }

    static function getYieldDataToExport($whereArr){
        //每次取十条
        $size = 10 ;
        $model = AdminUserFinanceExportDataRecord::create();

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
                //=====================
                $dataItem['details'] = [];

                if($dataItem['upload_data_id']){
                    $dataItem['upload_details'] = [];
                    $dataItem['data_details'] = [];
                    $uploadRes = AdminUserFinanceUploadDataRecord::findById($dataItem['upload_data_id']);
                    if($uploadRes){
                        $dataItem['upload_details'] = $uploadRes->toArray();
                    }

                    $dataRes = AdminUserFinanceData::findById($uploadRes['user_finance_data_id']);
                    if($dataRes){
                        $dataItem['data_details'] = $dataRes->toArray();
                    }
                }
                //======================

                yield $datas[] =  [
                    'id' => $dataItem['id'],
                    'entName' => $dataItem['data_details']['entName'],
                    'last_charge_date' => $dataItem['data_details']['last_charge_date'],
                    'year' => $dataItem['data_details']['year'],
                    'charge_year' => $dataItem['upload_details']['charge_year'],
                    'charge_year_start' => $dataItem['upload_details']['charge_year_start'],
                    'charge_year_end' => $dataItem['upload_details']['charge_year_end'],
                    'real_price' => $dataItem['upload_details']['real_price'],
                    'real_price_remark' => $dataItem['upload_details']['real_price_remark'],
                    'price_type_remark' => $dataItem['upload_details']['price_type_remark'],
                ];
            }

            $model = AdminUserFinanceExportDataRecord::create();
            foreach ($whereArr as $whereItem){
                $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate'])->limit($size);
            }

            $model->where('id', $lastId, '>')->limit($size);
            $res = $model ->all();
        }
    }

    public static function addExportRecord($requestData){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'export data record addExportRecord' => 'start',
               '$requestData' =>$requestData,
            ])
        );
        try {
           $res =  AdminUserFinanceExportDataRecord::create()->data([
                'user_id' => $requestData['user_id'], 
                'export_record_id' => $requestData['export_record_id'],   
                'user_finance_data_id' => $requestData['user_finance_data_id']?:0,
                'queue_id' =>$requestData['queue_id'],
                'upload_data_id' =>$requestData['upload_data_id'],
                'price' => $requestData['price'],
                'batch' => $requestData['batch'],
                'detail' => $requestData['detail'],
                'status' => $requestData['status'],   
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'export data record addExportRecord' => 'start',
                    '$requestData' =>$requestData,
                    'getMessage' => $e->getMessage(),
                ])
            );
        }  

        return $res;
    }

    public static function findByUserAndExportId($user_id,$export_id){
        $res =  AdminUserFinanceExportDataRecord::create()->where([
            'user_id' => $user_id,  
            'export_record_id' => $export_id,
            // 'status' => 1,  
        ])->all();

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceExportDataRecord::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

}
