<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

// use App\HttpController\Models\AdminRole;

class NewFinanceData extends ModelBase
{
    protected $tableName = 'new_finance_data';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function getByEntNameAndYear($entName,$year){
        $res = NewFinanceData::create()
            ->where(['entName' => $entName , 'year' => $year])
            ->get(); 

        // CommonService::getInstance()->log4PHP(
        //     [ 'res' =>$res]
        //  );
        return $res;
    } 
    
    public static function addRecord(
        $postData
    ){ 
        try {
           $res =  NewFinanceData::create()->data([
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
                'last_pull_api_time' => $postData['last_pull_api_time'],   
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

    public static function findByCondition($whereArr,$limit){
        $res =  NewFinanceData::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function findById($id){
        $res =  NewFinanceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

}
