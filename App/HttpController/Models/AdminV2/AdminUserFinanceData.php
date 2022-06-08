<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use self;

// use App\HttpController\Models\AdminRole;

class AdminUserFinanceData extends ModelBase
{
    /*
    
        该用户具体客户名单的收费
    */
    protected $tableName = 'admin_user_finance_data';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static $priceTytpeAnnually = 5;
    static $priceTytpeAnnuallyCname = '包年';
    static $priceTytpeNormal = 10;

    public static function addRecord($requestData){ 
        try {
           $res =  AdminUserFinanceData::create()->data([
                'user_id' => $requestData['user_id'],  
                'entName' => $requestData['entName'],  
                'year' => $requestData['year'],  
                'finance_data_id' => $requestData['finance_data_id'],  
                'price' => $requestData['price'],  
                'price_type' => $requestData['price_type'],  
                'cache_end_date' => $requestData['cache_end_date'],  
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
 
    public static function calculatePrice($id,$financeConifgArr){ 
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
        // 是年度收费
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){  
            self::updatePrice(
                $id,
                $chagrgeDetailsAnnuallyRes['HasChargedBefore'] ? 0 : $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                self::$priceTytpeAnnually
            );
        }  

        //收费方式二：按单年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
         
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){  
            self::updatePrice(
                $id,
                $chagrgeDetailsByYearsRes['HasChargedBefore'] ? 0 : $chagrgeDetailsByYearsRes['YearPrice'],
                self::$priceTytpeAnnually
            );
        } 

        return true; 
    }

    public static function getFinanceDataSourceDetail($adminFinanceDataId){
        $adminFinanceDataId;
        return [
            'pullFromApi' => true,
            'pullFromDb' => false,
        ];
    }

    //我们拉取运营商的时间间隔  
    //客户导出的时间间隔  
    public static function pullFinanceData($id,$financeConifgArr){  

        $res =  AdminUserFinanceData::create()
            ->where('id',$id) 
            ->get();  
 
        $postData = [
            'entName' => $res->getAttr('entName'),
            'code' => '',
            'beginYear' => $res->getAttr('year'),
            'dataCount' => 1,//取最近几年的
        ];
         
        // 根据缓存期和上次拉取财务数据时间 决定是取db还是取api
        $getFinanceDataSourceDetailRes = self::getFinanceDataSourceDetail($id);
        if($getFinanceDataSourceDetailRes['pullFromApi']){
            $res = (new LongXinService())->getFinanceData($postData, false);
            $postData = $res['data'];
            // 更新拉取时间 
            // 保存到db
            $addRes = NewFinanceData::addRecord(
                [
                    'entName' => $postData['entName'],  
                    'user_id' => $postData['user_id'],   
                    'year' => $postData['year'],   
                    'VENDINC' => $postData['VENDINC'],   
                    'ASSGRO' => $postData['ASSGRO'],   
                    'MAIBUSINC' => $postData['MAIBUSINC'],   
                    'TOTEQU' => $postData['TOTEQU'],   
                    'RATGRO' => $postData['RATGRO'],   
                    'PROGRO' => $postData['PROGRO'],   
                    'NETINC' => $postData['NETINC'],   
                    'SOCNUM' => $postData['SOCNUM'],   
                    'EMPNUM' => $postData['EMPNUM'],   
                    'status' => $postData['status'],   
                    'last_pull_api_time' => date('Y-m-d H:i:s',time()), 
                ]
            );
        } 

        return $addRes; 
    }

    public static function getChagrgeDetailsAnnually(
        $year,$financeConifgArr,$user_id,$entName
    ){ 
        if($financeConifgArr['annually_years']<0){
            return [
                'IsAnnually' => false,
                'AnnuallyPrice' => false,
                'HasChargedBefore' => false,
            ];
        }

        $annually_years_arr = explode(',',$financeConifgArr['annually_years']);

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

        //包年内 是否之前扣过钱
        $yearStr = '("'.implode('","',$annually_years_arr).'")';
        $sql = " select id from  `admin_user_finance_data`  
                    WHERE 
                        `year` in $yearStr  AND 
                        user_id = $user_id AND 
                        entName = $entName   

                    limit 1 ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        
        return [
            'IsAnnually' => true,
            'AnnuallyPrice' => $financeConifgArr['annually_price'],
            'HasChargedBefore' => empty($list) ? false : true,
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
        $sql = " select id from  `admin_user_finance_data`  
                    WHERE 
                        `year`  = $year  AND 
                        user_id = $user_id AND 
                        entName = $entName   
                    limit 1 ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        
        return [
            'IsChargeByYear' => true,
            'YearPrice' => $normal_years_price_arr[$year],
            'HasChargedBefore' => empty($list) ? false : true,
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
        
        return $info->update([
            'id' => $id,
            'status' => $status 
        ]);
    }

}