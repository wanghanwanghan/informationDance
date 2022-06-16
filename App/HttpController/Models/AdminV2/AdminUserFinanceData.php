<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceData extends ModelBase
{
    /*
    
        该用户具体客户名单的收费
    */
    protected $tableName = 'admin_user_finance_data';
    static $pullFinanceTimeInterval = 31104000;
    static $pullFinanceTimeIntervalCname = '我们从供应商拉取财务数据的时间间隔';
    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static $priceTytpeAnnually = 5;
    static $priceTytpeAnnuallyCname = '包年';
    static $priceTytpeNormal = 10;

    static $statusinit = 1;
    static $statusinitCname = '初始';

    static $statusNeedsConfirm = 5;
    static $statusNeedsConfirmCname = '待确认';

    static $statusConfirmedYes = 10;
    static $statusConfirmedYesCname = '已确认需要';

    static $statusConfirmedNo = 15;
    static $statusConfirmedNoCname = '已确认不需要';

    public static function addRecord($requestData){ 
        try {
           $res =  AdminUserFinanceData::create()->data([
                'user_id' => $requestData['user_id'],  
                'entName' => $requestData['entName'],  
                'year' => $requestData['year'],  
                'finance_data_id' => $requestData['finance_data_id']?:0,
                'price' => $requestData['price']?:0,
                'price_type' => $requestData['price_type']?:0,
                'cache_end_date' => $requestData['cache_end_date']?:0,
                'reamrk' => $requestData['reamrk']?:'',
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


    //check balance
    public static function checkBalance($id,$financeConifgArr){
        CommonService::getInstance()->log4PHP(
            'calculatePrice start  '.$id. '  conf '.json_encode($financeConifgArr)
        );
        $res =  AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();

        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = self::getChagrgeDetailsAnnually(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '包年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsAnnuallyRes)
        );
        // 是年度收费
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err1  update price error  IsAnnually '.$id
                );
            }
        }

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '按年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsByYearsRes)
        );
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsByYearsRes['YearPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err2  update price error  ChargeByYear '.$id
                );
            }
        }

        return true;
    }

    // 计算单价
    public static function calculatePrice($id,$financeConifgArr){
        CommonService::getInstance()->log4PHP(
            'calculatePrice start  '.$id. '  conf '.json_encode($financeConifgArr)
        );
        $res =  AdminUserFinanceData::create()
            ->where('id',$id) 
            ->get();

        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = self::getChagrgeDetailsAnnually(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '包年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsAnnuallyRes)
        );
        // 是年度收费
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){  
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err1  update price error  IsAnnually '.$id
                );
            }
        }  

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '按年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsByYearsRes)
        );
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsByYearsRes['YearPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err2  update price error  ChargeByYear '.$id
                );
            }
        } 

        return true; 
    }

    public static function getFinanceDataSourceDetail($adminFinanceDataId){
        // $adminFinanceDataId 上次拉取时间| 没超过一年 就不拉
        $financeData =  AdminUserFinanceData::create()
            ->where('id',$adminFinanceDataId)
            ->get()
            ->toArray();
        if(empty($financeData['last_pull_api_date'])){
            return [
                'pullFromApi' => true,
                'pullFromDb' => false,
            ];
        }

        if(
            (strtotime($financeData['last_pull_api_date']) -time()) > self::$pullFinanceTimeInterval
        ){
            return [
                'pullFromApi' => true,
                'pullFromDb' => false,
            ];
        }

        return [
            'pullFromApi' => false,
            'pullFromDb' => true,
        ];
    }

    //我们拉取运营商的时间间隔  
    //客户导出的时间间隔  
    public static function pullFinanceData($id,$financeConifgArr){

        $financeData =  AdminUserFinanceData::create()
            ->where('id',$id) 
            ->get()
            ->toArray();

        $postData = [
            'entName' => $financeData['entName'],
            'code' => '',
            'beginYear' =>$financeData['year'],
            'dataCount' => 1,//取最近几年的
        ];
         
        // 根据缓存期和上次拉取财务数据时间 决定是取db还是取api
        $getFinanceDataSourceDetailRes = self::getFinanceDataSourceDetail($id);
        CommonService::getInstance()->log4PHP(
            [
                '$postData' => $postData,
                '$getFinanceDataSourceDetailRes' => $getFinanceDataSourceDetailRes,
            ]
        );
        //需要从APi拉取
        if($getFinanceDataSourceDetailRes['pullFromApi']){
            $res = (new LongXinService())->getFinanceData($postData, false);
            $resData = $res['result']['data'];
            $resOtherData = $res['result']['otherData'];
            CommonService::getInstance()->log4PHP(
                [
                    'getFinanceData $postData' => $postData,
                    'getFinanceData $res' => $res,
                    'getFinanceData $resData' => $resData,
                ]
            );
            //更新拉取时间
            self::updateLastPullDate($id,date('Y-m-d H:i:s'));
            // 保存到db
            $dbDataArr = $resData[$financeData['year']];
            $dbDataArr['entName'] = $financeData['entName'];
            $dbDataArr['year'] = $financeData['year'];
            //设置是否需要确认
            $dbDataArr['status'] = self::getConfirmStatus($financeConifgArr,$dbDataArr);
            $addRes = NewFinanceData::addRecord($dbDataArr);
            if(!$addRes){
                return CommonService::getInstance()->log4PHP(
                    'pullFinanceData   err 1  add NewFinanceData failed '.json_encode($dbDataArr)
                );
            }
        }
        CommonService::getInstance()->log4PHP(
            'pullFinanceData   succeed '.json_encode($dbDataArr)
        );
        return true;
    }
    public  static  function getConfirmStatus($financeConifgArr,$dataItem){
        // 不需要确认
        if(!$financeConifgArr['needs_confirm']){
            return self::$statusConfirmedYes;
        }

        //暂时写死，后期需要的话自己配置
        $needsConfirmFields = [
            'VENDINC',
            'ASSGRO',
            'MAIBUSINC',
            'TOTEQU'
        ];
        foreach ($dataItem as $itemKey => $value){
            if(
                in_array($itemKey,$needsConfirmFields) &&
                empty($value)
            ){
                return self::$statusNeedsConfirm;
            }
        }

        return self::$statusConfirmedYes;
    }
    public static function getChagrgeDetailsAnnually(
        $year,$financeConifgArr,$user_id,$entName
    ){
        CommonService::getInstance()->log4PHP(
            'getChagrgeDetailsAnnually    '.$year. '  conf '.json_encode($financeConifgArr)
        );
        if($financeConifgArr['annually_years']<0){
            return [
                'IsAnnually' => false,
                'AnnuallyPrice' => false,
                'HasChargedBefore' => false,
            ];
        }

        $annually_years_arr =  json_decode($financeConifgArr['annually_years'],true);

        //不是包年年度
        if(
            !in_array(
               $year,
               $annually_years_arr
            )
       ){
          return [
              'IsAnnually' => false,
              'AnnuallyPrice' => false,
              'HasChargedBefore' => false,
          ];
       }

        //包年内
        // 是否有有效的数据
        $yearStr = '("'.implode('","',$annually_years_arr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND
                        `status`  =  ".self::$statusConfirmedYes."
                ";
        $validDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        CommonService::getInstance()->log4PHP(
            'getChagrgeDetailsAnnually   是否有有效的数据  $sql '. $sql
        );

        // 包年内全部数据
        $yearStr = '("'.implode('","',$annually_years_arr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND 
                ";
        $allDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        CommonService::getInstance()->log4PHP(
            'getChagrgeDetailsAnnually   包年内全部数据  $sql '. $sql
        );
//        //包年内是否有扣过钱
//        $sql = " select id from  `admin_user_finance_data`
//                    WHERE
//                        `year` in $yearStr  AND
//                        user_id = $user_id  AND
//                        entName = '$entName' AND
//                        `status`  =  ".self::$statusConfirmedYes." AND
//                        real_price > 0
//                    limit 1 ";
//        $Chargelist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
//        CommonService::getInstance()->log4PHP(
//            'getChagrgeDetailsAnnually   包年内是否有扣过钱  $Chargelist '. $sql
//        );

        return [
            'IsAnnually' => true,
            'AnnuallyPrice' => $financeConifgArr['annually_price'],
            //'HasChargedBefore' => empty($Chargelist) ? false : true,
            'ValidDataIds' => empty($validDatalist) ? false : array_column($validDatalist,'id'),
            'allDataIds' => empty($allDatalist) ? false : array_column($allDatalist,'id'),

            //'ChargedDate' => empty($validDatalist) ? '' : $validDatalist[0]['last_charge_date'],
        ]; 
    }

    // normal_years_price_json : {"2018":"100","2020":"300"}
    public static function getChagrgeDetailsByYear(
        $year,$financeConifgArr,$user_id,$entName
    ){ 
        $normal_years_price_arr = json_decode($financeConifgArr['normal_years_price_json'],true);
        if(empty($normal_years_price_arr)){
            return [
                'IsChargeByYear' => false,
                'YearPrice' => false,
                'HasChargedBefore' => false,
            ];
        } 

        //不是包年年度
        if(
            !in_array(
               $year,
               array_keys($normal_years_price_arr)
            )
       ){
          return [
                'IsChargeByYear' => false,
                'YearPrice' => false,
                'HasChargedBefore' => false,
          ];
       }

        //是否之前扣过钱 
//        $sql = " select id from  `admin_user_finance_data`
//                    WHERE
//                        `year`  = $year  AND
//                        user_id = $user_id  AND
//                        entName = '$entName' AND
//                        price > 0
//                    limit 1 ";
//        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
//        CommonService::getInstance()->log4PHP(
//            'getChagrgeDetailsByYear    '.$year. '  $sql '.$sql
//        );

        // 全部数据
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year`  = $year  AND
                        user_id = $user_id  AND
                        entName = '$entName'  
                    limit 1 ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        CommonService::getInstance()->log4PHP(
            'getChagrgeDetailsByYear    '.$year. '  $sql '.$sql
        );
        return [
            'IsChargeByYear' => true,
            'YearPrice' => $normal_years_price_arr[$year],
            'allDataIds' => empty($list) ? false : true,
        ]; 
    }

    static function getChargePrice($adminUserFinanceDataArr){
        return  $adminUserFinanceDataArr['price'];
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceData::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function findById($id){
        $res =  AdminUserFinanceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUserAndEntAndYear($userId,$entName,$year){
        $res =  AdminUserFinanceData::create()
            ->where([
                'user_id' => $userId,  
                'entName' => $entName,  
                'year' => $year,   
            ])
            ->get();  
        return $res;
    }

    public static function updatePrice($id,$price,$priceType){
        $info = AdminUserFinanceData::create()
                    ->where('id',$id)
                    ->get(); 
        
        return $info->update([
            'id' => $id,
            'price' => $price,  
            'price_type' => $priceType,  
        ]);
    }

    public static function updateStatus($id,$status){ 
        $info = AdminUserFinanceData::create()
                    ->where('id',$id)
                    ->get(); 
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateStatus failed  $id 不存在'.$id
            );
        }
        return $info->update([
            'id' => $id,
            'status' => $status 
        ]);
    }

    public static function updateLastPullDate($id,$date){
        $info = AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateLastPullDate failed  $id 不存在'.$id
            );
        }
        return $info->update([
            'id' => $id,
            'last_pull_api_date' => $date
        ]);
    }

    public static function updateLastChargeDate($id,$date){
        $info = AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateLastChargeDate failed  $id 不存在'.$id
            );
        }
        return $info->update([
            'id' => $id,
            'last_charge_date' => $date
        ]);
    }

    public static function updateCacheEndDate($id,$date,$cacheHours){
        $info = AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateCacheEndDate failed  $id 不存在'.$id
            );
        }

        return $info->update([
            'id' => $id,
            'cache_end_date' => date(
                'Y-m-d H:i',strtotime('+'.$cacheHours.' hours',strtotime($date))
            )
        ]);
    }

    function  setCostTimes(){

    }

}
