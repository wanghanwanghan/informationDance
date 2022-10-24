<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class OnlineGoodsCommissionGrantDetails extends ModelBase
{

    protected $tableName = 'online_goods_commission_grant_details';

    static  $input_type_in = 5;
    static  $input_type_in_cname = '收入';

    static  $input_type_out = 10;
    static  $input_type_out_cname = '支出';



    static  function  addRecordV2($info){
        //commission_order_id 订单id
        //commission_type 保险还是贷款
        //commission_data_type 谁分给谁的
//        $oldRes = self::findByCommissionOrderId($info['user_id'],$info['commission_id'],$info['type']);
//        if(
//            $oldRes
//        ){
//           return  $oldRes->getAttr('id');
//        }

        return OnlineGoodsCommissionGrantDetails::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  OnlineGoodsCommissionGrantDetails::create()->data([
                'user_id' => $requestData['user_id'],
                'commission_id' => $requestData['commission_id'],
                'amount' => $requestData['amount'],
                'commission_create_user_id' => $requestData['commission_create_user_id'],
                'commission_owner' => $requestData['commission_owner'],
                'type' => $requestData['type'],
                'state' => $requestData['state'],
                'remark' => $requestData['remark']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'getMessage' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findOneByCondition($whereArr){
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where($whereArr)
            ->get();
        return $res;
    }


    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsCommissionGrantDetails::findById($id);

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

    public static function findByConditionWithCountInfo($whereArr,$page,$pageSize){
        $model = OnlineGoodsCommissionGrantDetails::create()
                ->where($whereArr)
                ->page($page,$pageSize)
                ->order('id', 'DESC')
                ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page,$pageSize){
        $model = OnlineGoodsCommissionGrantDetails::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$pageSize)
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
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    //commission_order_id
    public static function findByCommissionId($commission_id,$user_id,$type){
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where('commission_id',$commission_id)
            ->where('user_id',$user_id)
            ->where('type',$type)
            ->get();
        return $res;
    }

    //commission_order_id
    public static function findAllByCommissionId($commission_order_id){
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where('commission_order_id',$commission_order_id)
            ->all();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  OnlineGoodsCommissionGrantDetails::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsCommissionGrantDetails::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_commission_grant_details` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
