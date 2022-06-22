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
    static $chargeTytpeFinanceCname = '财务导出';

    static $chargeTytpeAdd = 10;
    static $chargeTytpeAddCname = '充值';


    static  $chargeTypePreAdd = 15 ;
    static  $chargeTypePreAddCname =  '预充值' ; 

    static $chargeTytpeDele = 20;
    static $chargeTytpeDeleCname = '扣费';
    public static function getTypeCnameMaps(){

        return [
            self::$chargeTytpeFinance => self::$chargeTytpeFinanceCname,
            self::$chargeTytpeAdd => self::$chargeTytpeAddCname,
            self::$chargeTytpeDele => self::$chargeTytpeDeleCname,
        ];
    }

    public static function addRecord($requestData){
        try {
           $res =  FinanceLog::create()->data([
                'detailId' => intval($requestData['detailId'])?:0,
                'detail_table' => $requestData['detail_table']?:'',
                'price' => $requestData['price'],
                'userId' => $requestData['userId'],
                'type' => $requestData['type'],
                'batch' => $requestData['batch'],
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

    public static function addRecordV2($requestData){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'finance log  addRecordV2  '=> 'start',
                '$requestData' =>  $requestData
            ])
        );

        $res = self::findByBatch($requestData['batch']);
        if($res){
            return  $res->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function findByBatch($batch){
        $res =  FinanceLog::create()
            ->where([
                'batch' => $batch
            ])
            ->get();
        CommonService::getInstance()->log4PHP(
            json_encode([
                'finance log findByBatch  '=> 'start ',
                '$batch' =>  $batch,
                '$res' =>$res,
            ])
        );
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
    public static function findByConditionV2($whereArr,$page){

        $model = FinanceLog::create()
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
}
