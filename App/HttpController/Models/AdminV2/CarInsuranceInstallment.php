<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class CarInsuranceInstallment extends ModelBase
{

    protected $tableName = 'car_insurance_installment';

    static $status_init = 1;
    static $status_init_cname = '初始化';

    static $status_email_succeed = 5;
    static $status_email_succeed_cname = '发邮件成功';


    static  function  addRecordV2($info){

        if(
            self::findByUserIdAndEntName($info['ent_name'],$info['user_id'])
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'CarInsuranceInstallment has old  record'=>[
                        'ent_name'=>$info['ent_name'],
                        'user_id'=>$info['user_id'],
                    ],

                ])
            );
            return  true;
        }

        return CarInsuranceInstallment::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  CarInsuranceInstallment::create()->data([
                'user_id' => $requestData['user_id'],
                'product_id' => $requestData['product_id']?:0,
                'ent_name' => $requestData['ent_name'],
                'legal_phone' => $requestData['legal_phone'],
                'order_no' => $requestData['order_no'],
               'legal_person' => $requestData['legal_person'],
               'legal_person_id_card' => $requestData['legal_person_id_card'],
                'url' => $requestData['url']?:'',
                'raw_return' => $requestData['raw_return']?:'',
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
        $res =  CarInsuranceInstallment::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = CarInsuranceInstallment::findById($id);

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
        $model = CarInsuranceInstallment::create()
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
        $model = CarInsuranceInstallment::create();
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
        $res =  CarInsuranceInstallment::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUserIdAndEntName($ent_name,$user_id){
        $res =  CarInsuranceInstallment::create()
            ->where('ent_name',$ent_name)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }


    public static function findByName($user_id,$name,$product_id){
        $res =  CarInsuranceInstallment::create()
            ->where('name',$name)
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = CarInsuranceInstallment::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `car_insurance_installment` 
                            $where
        ";
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


    public static function getDataLists($where,$page){
        $res =   CarInsuranceInstallment::findByConditionV2(
            $where,$page
        );
        $newData = [];
        foreach ($res['data'] as &$dataItem){
            $dataArr = json_decode(
                $dataItem['post_params'],true
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataArr' => $dataArr,
                    '$dataItem'=>$dataItem,

                    'product_id'=>$dataArr['product_id'],
                    'post_params'=>$dataItem['post_params']
                ])
            );
            $dataRes = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $dataArr['product_id']
            );

            $tmp = [
                'id'=>$dataItem['id'],
                'title' =>$dataRes['data']['title'],
                'description' =>$dataRes['data']['description'],
                'logo' =>$dataRes['data']['logo'],
                'created_at' => date('Y-m-d H:i:s',$dataItem['created_at']),
            ];
            $tmp = array_merge($tmp,$dataArr);
            $newData[] = $tmp;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataRes' => $dataRes
                ])
            );
        }

        return [
            'data' => $newData,
            'total' => $res['total'],
        ];
    }
}
