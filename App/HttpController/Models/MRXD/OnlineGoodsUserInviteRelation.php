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
//        $oldRes = self::findByUserAndInvite($info['user_id'],$info['invite_by']);
        $oldRes = self::findByUser($info['user_id']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return OnlineGoodsUserInviteRelation::addRecord(
            $info
        );
    }

    static function getFansNums($userInfo){

        //VIP
        if(
            OnlineGoodsUser::IsVip($userInfo)
        ){

            $invitors = OnlineGoodsUserInviteRelation::getVipsAllInvitedUser($userInfo['id']);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'getFansNums' => [
                        'uid'=>$userInfo['id'],
                        'uname'=>$userInfo['user_name'],
                        'IsVip'=>true,
                        '$invitors'=>count($invitors),
                    ]
                ])
            );
        }else{
            $invitors =  OnlineGoodsUserInviteRelation::getDirectInviterInfo($userInfo['id']);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'getFansNums' => [
                        'uid'=>$userInfo['id'],
                        'uname'=>$userInfo['user_name'],
                        'IsVip'=>false,
                        '$invitors'=>count($invitors),
                    ]
                ])
            );
        }

        return count($invitors);
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

    static function IsFans($fans_id,$uid){
        return self::findAllByCondition([
            'user_id'=>$fans_id,
            'invite_by'=>$uid,
        ]);
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

    public static function findByUser($user_id){
        $res =  OnlineGoodsUserInviteRelation::create()
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }


    public static function setData($id,$field,$value){
        $info = OnlineGoodsUserInviteRelation::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    //获取所有非vip用户的邀请
    static function getAllInvitedUser($userId){
        $res = self::findAllByCondition([
            'invite_by' =>$userId
        ]);
        return $res;
    }

    //获取所有vip用户的邀请
    static function getVipsAllInvitedUser($userId){
        $res = self::findAllByCondition([
            'invite_by' =>$userId
        ]);

        $return = [];
        while (!empty($res)){
            foreach ($res as $value){
                $return[] = $value;
            }
            $res = self::getAllInvitedUser($value['user_id']);
        }

        return $return;
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

    // 获取直接邀请人
    static function getDirectInviterInfo($userId){
        $res = self::findByUser($userId);
        if(empty($res)){
            return  [];
        }
        $tmpUsersInfo = OnlineGoodsUser::findById($res->invite_by);
        return $tmpUsersInfo?$tmpUsersInfo->toArray():[];
    }

    // 获取VIP邀请人
    static function getVipInviterInfo($userId){
        //先检查自己是不是vip
        $tmpUser = OnlineGoodsUser::findById($userId);
        $tmpUser = $tmpUser->toArray();
        if(
            OnlineGoodsUser::IsVip($tmpUser)
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'getVipInviterInfo_$userId'=>$userId,
                    'IsVip'=>true,
                ])
            );
            return  $tmpUser;
        };


        $res = self::findByUser($userId);

        $topInvitor =  0 ;
        while (true){
            $tmpInfo = $res->toArray();
            $res = self::findByUser($tmpInfo['invite_by']);
            if(empty($res)){
                $topInvitor = $tmpInfo['invite_by'];
                break;
            }
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'getVipInviterInfo_$userId'=>$userId,
                '$topInvitor'=>$topInvitor,
            ])
        );
        if($topInvitor <= 0 ){
            return  [];
        }

        $topInvitorInfo = OnlineGoodsUser::findById($topInvitor);
        $topInvitorInfo = $topInvitorInfo->toArray();
        if(
            OnlineGoodsUser::IsVip($topInvitorInfo)
        ){
            return  $topInvitorInfo;
        }
        return [];
    }

}
