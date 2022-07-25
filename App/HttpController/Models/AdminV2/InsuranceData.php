<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class InsuranceData extends ModelBase
{

    protected $tableName = 'insurance_data';

    static $status_init = 1;
    static $status_init_cname = '初始化';


    static $status_email_succeed = 5;
    static $status_email_succeed_cname = '发邮件成功';


    static  function  addRecordV2($info){

        if(
            self::findByName($info['name'],$info['product_id'])
        ){
            return  true;
        }

        return InsuranceData::addRecord(
            $info
        );
    }


    /**
    id
    type
    name
    business_license_file
    business_license
    type_of_work
    number_of_people
    death_injury_limit
    medical_limit
    loss_of_work
    hospital_allowance
    license_plate
    engine_number
    frame_number
    work_area
    new_equipment_price
    additional_insurance
    equipment_type
    date_of_manufacture
    other_demands
    last_year_underwriting_company
    last_3_years_compensation_situation
    status
    created_at
    updated_at
     */
    public static function addRecord($requestData){

        try {
           $res =  InsuranceData::create()->data([
                'post_params' => $requestData['post_params'],
                 'type' => $requestData['type'],
                'name' => $requestData['name'],
                'status' => $requestData['status']?:1,
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function cost(){
        $start = microtime(true);
        $startMemory = memory_get_usage();

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'memory_use' => round((memory_get_usage()-$startMemory)/1024/1024,3).' M',
                'costs_seconds '=> number_format(microtime(true) - $start,3)
            ])
        );
    }

    public static function findAllByCondition($whereArr){
        $res =  InsuranceData::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = InsuranceData::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = InsuranceData::create()
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

    public static function findByConditionV2($whereArr,$page){
        $model = InsuranceData::create();
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
        $res =  InsuranceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByName($name,$product_id){
        $res =  InsuranceData::create()
            ->where('name',$name)
            ->where('product_id',$product_id)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = InsuranceData::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `insurance_data` 
                            $where
        ";
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
