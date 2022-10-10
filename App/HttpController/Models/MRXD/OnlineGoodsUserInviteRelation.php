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

class OnlineGoodsUserInviteRelation extends ModelBase
{

    protected $tableName = 'online_goods_user_invite_relation';

    static  function  addRecordV2($info){
        $oldRes = self::findByUserAndInvite($info['user_id'],$info['invite_by']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return OnlineGoodsUserInviteRelation::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  OnlineGoodsUserInviteRelation::create()->data([
                'user_id' => $requestData['user_id'],
                'invite_by' => $requestData['invite_by'],
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
                    'err_msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  OnlineGoodsUserInviteRelation::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsUserInviteRelation::findById($id);

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
        $model = OnlineGoodsUserInviteRelation::create()
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
        $model = OnlineGoodsUserInviteRelation::create();
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
        $res =  OnlineGoodsUserInviteRelation::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUserAndInvite($user_id,$invite_by){
        $res =  OnlineGoodsUserInviteRelation::create()
            ->where('user_id',$user_id)
            ->where('invite_by',$invite_by)
            ->get();
        return $res;
    }


    public static function setData($id,$field,$value){
        $info = OnlineGoodsUserInviteRelation::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_user` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
