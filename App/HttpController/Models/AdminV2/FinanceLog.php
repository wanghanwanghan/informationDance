<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class FinanceLog extends ModelBase
{
    /*
     该用户具体收费日志
    */
    protected $tableName = 'charge_log';

    static $chargeTytpeFinance = 5;
    static $chargeTytpeFinanceCname = '收费类型-财务导出';

    public static function addRecord($requestData){
        try {
           $res =  FinanceLog::create()->data([
                'detailId' => intval($requestData['detailId']),
                'detail_table' => $requestData['detail_table']?:'',
                'price' => $requestData['price'],
                'userId' => $requestData['userId'],
                'type' => $requestData['type'],
                'title' => $requestData['title']?:'',
                'detail' => $requestData['detail']?:'',
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'FinanceLog addRecord sql err',
                    $e->getMessage(),
                ])
            );  
        }
        return $res;
    }


    public static function findByCondition($whereArr,$limit){
        $res =  FinanceLog::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function findById($id){
        $res =  FinanceLog::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUser($userId){
        $res =  FinanceLog::create()
            ->where([
                'userId' => $userId,
            ])
            ->all();
        return $res;
    }

    public static function findByUserAndType($userId,$type){
        $res =  FinanceLog::create()
            ->where([
                'userId' => $userId,
                'type' => $type,
            ])
            ->all();
        return $res;
    }

    static  function charge($userId,$money,$type,$details){

    }

}
