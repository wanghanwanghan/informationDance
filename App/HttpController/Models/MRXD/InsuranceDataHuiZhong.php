<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;

// use App\HttpController\Models\AdminRole;

class InsuranceDataHuiZhong extends ModelBase
{
    protected $tableName = 'insurance_data_hui_zhong';
    static  function  addRecordV2($info){
        if(
            self::findByName($info['user_id'],$info['ent_name'],$info['product_id'])
        ){
            return  true;
        }

        return OnlineGoodsUser::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  InsuranceDataHuiZhong::create()->data([
                'product_id' => $requestData['product_id'],
                'ent_name' => $requestData['ent_name']?:'',
                'business_license_file' => $requestData['business_license_file']?:'',
                'id_card_front_file' => $requestData['id_card_front_file']?:'',
                'id_card_back_file' => $requestData['id_card_back_file']?:'',
                'public_account' => $requestData['public_account']?:'',
                'legal_person_phone' => $requestData['legal_person_phone']?:'',
               'business_license' => $requestData['business_license']?:'',
               'status' => $requestData['status']?:1,
               'user_id' => $requestData['user_id'],
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


    public static function findAllByCondition($whereArr){
        $res =  InsuranceDataHuiZhong::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = InsuranceDataHuiZhong::findById($id);

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
        $model = InsuranceDataHuiZhong::create()
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
        $model = InsuranceDataHuiZhong::create();
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
        $res =  InsuranceDataHuiZhong::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByToken($token){
        $res =  InsuranceDataHuiZhong::create()
            ->where('token',$token)
            ->get();
        return $res;
    }

    public static function findByName($user_id,$ent_name,$product_id){
        $res =  InsuranceDataHuiZhong::create()
            ->where('ent_name',$ent_name)
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = InsuranceDataHuiZhong::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `insurance_data_hui_zhong` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }
}
