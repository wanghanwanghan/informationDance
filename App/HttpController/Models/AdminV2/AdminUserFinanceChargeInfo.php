<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceChargeInfo extends ModelBase
{

    protected $tableName = 'admin_user_finance_data_charge_info';

    static  $state_init = 1;
    static  $state_init_cname =  '初始';

    static  $state_succeed = 10;
    static  $state_succeed_cname =  '成功';

    static  $state_failed = 20;
    static  $state_failed_cname =  '失败';

    static  $price_type_annual = 5;
    static  $price_type_annual_cname = '按包年扣费';

    static  $price_type_single = 10;
    static  $price_type_single_cname = '按单年扣费';


    public static function updatePriceById(
        $id,$price,$priceType
    ){
        $info = AdminUserFinanceChargeInfo::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'price' => $price,
            'price_type' => $priceType,
        ]);
    }


    public static function addRecord($requestData){
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'finance charge info  addRecord' => 'start',
//                '$requestData' =>$requestData,
//
//            ])
//        );
        try {
           $res =  AdminUserFinanceChargeInfo::create()->data([
                'user_id' => $requestData['user_id'],
                'batch' => $requestData['batch'],
                'entName' => $requestData['entName'],
                'start_year' => $requestData['start_year']?:'',
                'end_year' => $requestData['end_year']?:'',
                'year' => $requestData['year']?:'',
                'price' => $requestData['price'],
                'price_type' => $requestData['price_type'],
                'status' => $requestData['status']?:self::$state_init,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceChargeInfo入库异常' =>$e->getMessage(),
                ],JSON_UNESCAPED_UNICODE)
            );
        }  

        return $res;
    }

    public static function addRecordV2($requestData){
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'finance charge info  addRecordV2' => 'start',
//                '$requestData' =>$requestData,
//
//            ])
//        );
        $res = self::findByBatch($requestData['batch']);


        if($res){
            return  $res->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceChargeInfo::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();
        return $res;
    }

    public static function findByBatch($batch){
        $res =  AdminUserFinanceChargeInfo::create()
            ->where([
                'batch' => $batch
            ])
            ->get();
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'finance charge info  findByBatch' => 'start',
//                '$batch' =>$batch,
//                '$res' =>$res,
//
//            ])
//        );
        return $res;
    }

    public static function ifChargedBefore($userId,$entName,$year){
        $res =  AdminUserFinanceChargeInfo::create()
            ->where([
                'user_id' => $userId,
                'entName' => $entName,
                'year' => $year,
            ])
            ->order('id', 'DESC')
            ->get();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'ifChargedBefore . ',
                '$userId' => $userId,
                '$entName' => $entName,
                '$year' => $year,
                'return '=>$res?true:false
            ])
        );
        return $res;
    }

    public static function ifChargedBeforeV2($userId,$entName,$yearStart,$yearEnd){
        $res =  AdminUserFinanceChargeInfo::create()
            ->where([
                'user_id' => $userId,
                'entName' => $entName,
                'start_year' => $yearStart,
                'end_year' => $yearEnd,
            ])
            ->order('id', 'DESC')
            ->get();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'ifChargedBeforeV2 ',
                '$userId' => $userId,
                '$entName' => $entName,
                '$yearStart' => $yearStart,
                '$yearEnd' =>$yearEnd,
                'retrun ' => $res?true:false
            ])
        );
        return $res;
    }

    public static function findById($id){
        $res =  AdminUserFinanceChargeInfo::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }


    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `admin_user_finance_data_charge_info` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
