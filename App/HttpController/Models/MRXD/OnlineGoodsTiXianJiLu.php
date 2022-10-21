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

class OnlineGoodsTiXianJiLu extends ModelBase
{

    protected $tableName = 'online_goods_ti_xian_ji_lu';


    static  function  addRecordV2($info){
        $oldRes = self::findByPhone($info['phone']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return OnlineGoodsTiXianJiLu::addRecord(
            $info
        );
    }



    public static function addRecord($requestData){
        try {
           $res =  OnlineGoodsTiXianJiLu::create()->data([
                'user_id' => $requestData['user_id'],
                'amount' => $requestData['amount'],
                'remark' => $requestData['remark'],
                'audit_state' => $requestData['audit_state'],
                'audit_details' => $requestData['audit_details'],
                'pay_state' => $requestData['pay_state'],
                'pay_details' => $requestData['pay_details'],
                'da_kuan_type' => $requestData['da_kuan_type'],
                'kai_hu_hang' => $requestData['kai_hu_hang'],
                'kai_hu_ming' => $requestData['kai_hu_ming'],
                'yin_hang_ka_hao' => $requestData['yin_hang_ka_hao'],
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
        $res =  OnlineGoodsTiXianJiLu::create()
            ->where($whereArr)
            ->all();
        return $res;
    }


    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = OnlineGoodsTiXianJiLu::create()
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
        $model = OnlineGoodsTiXianJiLu::create();
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
        $res =  OnlineGoodsTiXianJiLu::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }


    public static function findByPhone($phone){
        $res =  OnlineGoodsTiXianJiLu::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsTiXianJiLu::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_ti_xian_ji_lu` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
