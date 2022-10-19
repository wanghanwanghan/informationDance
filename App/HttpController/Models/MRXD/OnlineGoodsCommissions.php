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

class OnlineGoodsCommissions extends ModelBase
{

    protected $tableName = 'online_goods_commissions';

    static  $commission_type_bao_xian = 5;
    static  $commission_type_bao_xian_cname = '保险';

    static  $commission_type_dai_kuan  = 10;
    static  $commission_type_dai_kuan_cname  = '贷款';

    static  $commission_state_init  = 5;
    static  $commission_state_init_cname  = '待设置分佣比例';

    static  $commission_state_seted  = 10;
    static  $commission_state_seted_cname  = '已设置分佣比例';

    /**
    信动给VIP分佣：信动—>A
    VIP给邀请人分佣：信动—>A
    邀请人给被邀请人分佣：C→D
     */
    static  $commission_data_type_xindong_to_vip  = 5;
    static  $commission_data_type_xindong_to_vip_cname  = '信动给VIP分佣';
    static  $commission_data_type_vip_to_invitor  = 10;
    static  $commission_data_type_vip_to_invitor_cname  = 'VIP给邀请人分佣';
    static  $commission_data_type_invitor_to_user  = 15;
    static  $commission_data_type_invitor_to_user_cname  = '邀请人给被邀请人分佣';


    static  function  addRecordV2($info){
        $oldRes = self::findByCommissionOrderId($info['commission_order_id'],$info['commission_type'],$info['commission_data_type']);
        if(
            $oldRes
        ){
           return  $oldRes->getAttr('id');
        }

        return OnlineGoodsCommissions::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  OnlineGoodsCommissions::create()->data([
                'user_id' => $requestData['user_id'],
                'commission_create_user_id' => $requestData['commission_create_user_id'],
                'commission_owner' => $requestData['commission_owner'],
                'commission_type' => $requestData['commission_type'],
                'commission_data_type' => $requestData['commission_data_type'],
                'comission_rate' => $requestData['comission_rate'],
                'commission_order_id' => $requestData['commission_order_id'],
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
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  OnlineGoodsCommissions::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsCommissions::findById($id);

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
        $model = OnlineGoodsCommissions::create()
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
        $model = OnlineGoodsCommissions::create();
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
        $res =  OnlineGoodsCommissions::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByCommissionOrderId($commission_order_id,$commission_type,$commission_data_type){
        $res =  OnlineGoodsCommissions::create()
            ->where('commission_order_id',$commission_order_id)
            ->where('commission_type',$commission_type)
            ->where('commission_data_type',$commission_data_type)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  OnlineGoodsCommissions::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsCommissions::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_commissions` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    static function addCommissionInfoByOrderInfo($orderInfo){
       
        // 基准金额  amount
        // 置金用户
        $zhiJinUserInfo = OnlineGoodsUser::findByPhone($orderInfo['zhijin_phone']);
        $zhiJinUserInfo = $zhiJinUserInfo->toArray();

        //直接邀请人
        $directInvitorInfo = OnlineGoodsUserInviteRelation::getDirectInviterInfo($zhiJinUserInfo['id']);
        //vip邀请人
        $VipInvitorInfo = OnlineGoodsUserInviteRelation::getVipInviterInfo($zhiJinUserInfo['id']);

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '$directInvitorInfo' => $directInvitorInfo,
                '$VipInvitorInfo' => $VipInvitorInfo,
            ])
        );
        return ;
        //信动给VIP分佣
        if($VipInvitorInfo){
            $state = OnlineGoodsCommissions::$commission_state_seted;
            OnlineGoodsCommissions::addRecordV2(
                [
                    //受益人
                    'user_id' => $VipInvitorInfo['id'],
                    //收益创造者
                    'commission_create_user_id' => $zhiJinUserInfo['id'],
                    //发放人
                    'commission_owner' => 99999,
                    'comission_rate' => 15,
                    'commission_type' => OnlineGoodsCommissions::$commission_type_dai_kuan,
                    'commission_data_type' => OnlineGoodsCommissions::$commission_data_type_xindong_to_vip,
                    'state' => $state,
                    'commission_order_id' => $id,
                    'remark' => '信动给VIP分佣',
                ]
            );
        }
        //VIP给邀请人分佣
        if($VipInvitorInfo&&$directInvitorInfo){
            $state = OnlineGoodsCommissions::$commission_state_init;
            OnlineGoodsCommissions::addRecordV2(
                [
                    //受益人
                    'user_id' => $directInvitorInfo['id'],
                    //收益创造者
                    'commission_create_user_id' => $zhiJinUserInfo['id'],
                    //发放人
                    'commission_owner' => $VipInvitorInfo['id'],
                    'comission_rate' => 15,
                    'commission_type' => OnlineGoodsCommissions::$commission_type_dai_kuan,
                    'commission_data_type' => OnlineGoodsCommissions::$commission_data_type_vip_to_invitor,
                    'state' => $state,
                    'commission_order_id' => $id,
                    'remark' => 'VIP给邀请人分佣',
                ]
            );
        }
        //邀请人给被邀请人分佣
        if($directInvitorInfo){
            $state = OnlineGoodsCommissions::$commission_state_init;
            OnlineGoodsCommissions::addRecordV2(
                [
                    //受益人
                    'user_id' => $zhiJinUserInfo['id'],
                    //收益创造者
                    'commission_create_user_id' => $zhiJinUserInfo['id'],
                    //发放人
                    'commission_owner' => $directInvitorInfo['id'],
                    'comission_rate' => 15,
                    'commission_type' => OnlineGoodsCommissions::$commission_type_dai_kuan,
                    'commission_data_type' => OnlineGoodsCommissions::$commission_data_type_invitor_to_user,
                    'state' => $state,
                    'commission_order_id' => $id,
                    'remark' => 'VIP给邀请人分佣',
                ]
            );
        }

        return true;
    }


}
