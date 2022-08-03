<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;

// use App\HttpController\Models\AdminRole;

class OnlineGoodsUser extends ModelBase
{

    protected $tableName = 'online_goods_user';

    static  $source_online_goods = 1;
    static  $source_online_goods_cname =  '网货平台';

    static  $source_self_register = 2;
    static  $source_self_register_cname =  '自行注册';

    static  $source_by_promote = 3;
    static  $source_by_promote_cname =  '推广注册';

    static function  addDailySmsNums($phone){
        //每日发送次数限制
        $daily_limit_key = 'daily_online_sendSms_'.$phone;
        $nums =  ConfigInfo::getRedisBykey($daily_limit_key);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'addDailySmsNums ' => [
                    '$nums'=>$nums,
                    '$daily_limit_key' => $daily_limit_key
                ],
            ])
        );
        if($nums <= 0 ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'addDailySmsNums ' => [
                        'setRedisBykey = '=>1,
                    ],
                ])
            );
            return ConfigInfo::setRedisBykey($daily_limit_key,intval($nums) + 1,60*60*24);
        }
        else{
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'addDailySmsNums ' => [
                        'setRedisBykey +='=>1,
                    ],
                ])
            );
            return ConfigInfo::incrRedisBykey($daily_limit_key);
        }
    }

    static function  setRandomDigit($phone,$digit){
        return ConfigInfo::setRedisBykey('online_sms_code_'.$phone,$digit,600);
    }

    static function  getRandomDigit($phone){
        return ConfigInfo::getRedisBykey('online_sms_code_'.$phone);
    }

    static  function  createRandomDigit(){
        return random_int(100000, 999999);
    }

    static function  checkDailySmsNums($phone){
        //每日发送次数限制
        $daily_limit_key = 'daily_online_sendSms_'.$phone;
        $nums =  ConfigInfo::getRedisBykey($daily_limit_key);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'checkDailySmsNums ' => [
                    'setRedisBykey '=>  [
                        '$nums'=>$nums,
                        '$daily_limit_key'=>$daily_limit_key
                    ],
                ],
            ])
        );
        if($nums >= 20 ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'checkDailySmsNums failed' => [
                        '$nums'=>$nums,
                        '$daily_limit_key'=>$daily_limit_key
                    ],
                ])
            );
        }
        return true;
    }

    static  function  addRecordV2($info){

        if(
            self::findByPhone($info['phone'])
        ){
            return  true;
        }

        return OnlineGoodsUser::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  OnlineGoodsUser::create()->data([
                'source' => $requestData['source']?:self::$source_self_register,
                'user_name' => $requestData['user_name']?:'',
                'phone' => $requestData['phone']?:'',
                'password' => $requestData['password']?:'',
                'email' => $requestData['email']?:'',
                'money' => $requestData['money']?:'',
                'token' => $requestData['token']?:'',
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
        $res =  OnlineGoodsUser::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsUser::findById($id);

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
        $model = OnlineGoodsUser::create()
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
        $model = OnlineGoodsUser::create();
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
        $res =  OnlineGoodsUser::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByToken($token){
        $res =  OnlineGoodsUser::create()
            ->where('token',$token)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  OnlineGoodsUser::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsUser::findById($id);
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
