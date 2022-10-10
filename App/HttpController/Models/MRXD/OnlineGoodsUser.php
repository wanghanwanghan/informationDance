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

class OnlineGoodsUser extends ModelBase
{

    protected $tableName = 'online_goods_user';

    static  $source_online_goods = 1;
    static  $source_online_goods_cname =  '网货平台';

    static  $source_self_register = 2;
    static  $source_self_register_cname =  '自行注册';

    static  $source_by_promote = 3;
    static  $source_by_promote_cname =  '推广注册';

    static $level_vip = 1 ;
    static $level_vip_cname =  'vip客户' ;

    // 是否vip
    static function IsVip($userInfo){
        return $userInfo['level'] == self::$level_vip;
    }

    // 是否vip
    static function IsVipV2($id){
        $userDataModel = self::findById($id);
        $userInfo = $userDataModel->toArray();
        return  self::IsVip($userInfo);
    }

    static function  addDailySmsNums($phone,$prx = "daily_online_sendSms_"){
        //每日发送次数限制
        $daily_limit_key = $prx.$phone;
        $nums =  ConfigInfo::getRedisBykey($daily_limit_key);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'addDailySmsNums_$nums'=>$nums,
                'addDailySmsNums_$daily_limit_key' => $daily_limit_key,
            ])
        );
        if($nums <= 0 ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'addDailySmsNums_setRedisBykey = '=>1,
                ])
            );
            return ConfigInfo::setRedisBykey($daily_limit_key,intval($nums) + 1,60*60*24);
        }
        else{
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'addDailySmsNums_setRedisBykey +='=>1,
                ])
            );
            return ConfigInfo::incrRedisBykey($daily_limit_key);
        }
    }

    static function  addDailySmsNumsV2($phone,$prx = "daily_online_sendSms_"){
        $redis = Redis::defer('redis');
        $redis->select(ConfigInfo::$redis_db_num);

        //每日次数
        $daily_limit_key = $prx.$phone;
        //key对应的时间
        $daily_limit_key2 = $prx.$phone.'_time';

        $nums = $redis->get($daily_limit_key);
        $dates = $redis->get($daily_limit_key2);

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sms_daily_send_nums'=>[
                    'param_phone'=>$phone,
                    'param_$prx'=>$prx,
                    'daliy_send_nums_key'=>$daily_limit_key,
                    'daliy_send_nums_value'=>$nums,
                    'daliy_send_nums_date_key'=>$daily_limit_key2,
                    'daliy_send_nums_date_value'=>$dates,
                ]
            ])
        );

        //之前没有过
        if($nums <= 0){
            //设置KEY
            $redis->set($daily_limit_key,1);
            //设置KEY的时间
            $redis->set($daily_limit_key2,date('Ymd'),60*60*24);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sms_daily_send_nums'=>[
                        'param_phone'=>$phone,
                        'param_$prx'=>$prx,
                        'msg'=>'daliy_send_nums_value_is_zero_._rest',
                        '$daily_limit_key'=>$daily_limit_key,
                        '$daily_limit_value'=>1,
                        '$daily_limit_key_date_key'=>$daily_limit_key2,
                        '$daily_limit_key_date_value'=>date('Ymd'),
                    ]
                ])
            );
        }



        //如果过期了1
        if($dates <= 0){
            //设置KEY
            $redis->set($daily_limit_key,1);
            //设置KEY的时间
            $redis->set($daily_limit_key2,date('Ymd'),60*60*24);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sms_daily_send_nums'=>[
                        'param_phone'=>$phone,
                        'param_$prx'=>$prx,
                        'msg'=>'daliy_send_nums_value_is_expuired_._rest',
                        '$daily_limit_key'=>$daily_limit_key,
                        '$daily_limit_value'=>1,
                        '$daily_limit_key_date_key'=>$daily_limit_key2,
                        '$daily_limit_key_date_value'=>date('Ymd'),
                    ]
                ])
            );
        }
        //如果过期了2
        if($dates < date('Ymd')){
            //设置KEY
            $redis->set($daily_limit_key,1);
            //设置KEY的时间
            $redis->set($daily_limit_key2,date('Ymd'),60*60*24);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sms_daily_send_nums'=>[
                        'param_phone'=>$phone,
                        'param_$prx'=>$prx,
                        'msg'=>'daliy_send_nums_value_is_expuired_2._rest',
                        '$daily_limit_key'=>$daily_limit_key,
                        '$daily_limit_value'=>1,
                        '$daily_limit_date_key'=>$daily_limit_key2,
                        '$daily_limit_date_value'=>date('Ymd'),
                    ]
                ])
            );
        }

        //更新KEY
        $nums = $redis->get($daily_limit_key);
        $redis->set($daily_limit_key,$nums+1);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sms_daily_send_nums'=>[
                    'param_phone'=>$phone,
                    'param_$prx'=>$prx,
                    'msg'=>'rest_daily_send_nums',
                    '$daily_limit_key'=>$daily_limit_key,
                    '$daily_limit_value'=>$nums,
                ]
            ])
        );
    }
    static function  getDailySmsNumsV2($phone,$prx = "daily_online_sendSms_"){
        $redis = Redis::defer('redis');
        $redis->select(ConfigInfo::$redis_db_num);

        //每日次数
        $daily_limit_key = $prx.$phone;
        //key对应的时间
        $daily_limit_key2 = $prx.$phone.'_time';

        $nums = $redis->get($daily_limit_key);
        $dates = $redis->get($daily_limit_key2);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'getDailySmsNumsV2'=>[
                    'param_$phone'=>$phone,
                    'daily_send_nums_key'=>$daily_limit_key,
                    'daily_send_nums_value'=>$nums,
                    'daily_send_nums_date_key'=>$daily_limit_key2,
                    'daily_send_nums_date_value'=>$dates,
                ]
            ])
        );
        return $nums;
    }

    static function  setRandomDigit($phone,$digit,$prx="online_sms_code_"){
        $res = ConfigInfo::setRedisBykey($prx.$phone,$digit,600);;
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'setRandomDigit' => [
                    'params_$phone'=>$phone,
                    'params_$digit'=>$digit,
                    'params_$prx'=>$prx,
                    'redis_set_key'=>$prx.$phone,
                    'redis_set_$res'=>$res,
                ],
            ])
        );

        return $res;
    }

    static function  getRandomDigit($phone,$prx="online_sms_code_"){
        $key = $prx.$phone;
        $res =  ConfigInfo::getRedisBykey($key);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'getRandomDigit' => [
                    'param_$phone'=>$phone,
                    'param_$prx'=>$prx,
                    'redis_key'=>$key,
                    'redis_$res'=>$res,
                ],
            ])
        );
        return $res;
    }

    static  function  createRandomDigit(){
        return random_int(100000, 999999);
    }

    static function  checkDailySmsNums($phone,$prx='daily_online_sendSms_'){
        //每日发送次数限制
        $daily_limit_key = $prx.$phone;
        $nums =  ConfigInfo::getRedisBykey($daily_limit_key);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'checkDailySmsNums_$nums'=>$nums,
                'checkDailySmsNums_$daily_limit_key'=>$daily_limit_key
            ])
        );
        if($nums >= 2 ){

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'checkDailySmsNums_failed_$nums'=>$nums,
                    'checkDailySmsNums_failed_$daily_limit_key'=>$daily_limit_key
                ])
            );
            return  false;
        }
        return true;
    }

    static  function  addRecordV2($info){
        $oldRes = self::findByPhone($info['phone']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
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

    public static function sendMsg($phone,$key){

        //记录今天发了多少次
        OnlineGoodsUser::addDailySmsNumsV2($phone,'daily_'.$key.'_sendSms_');

        //每日发送次数限制
        $res = OnlineGoodsUser::getDailySmsNumsV2($phone,'daily_'.$key.'_sendSms_');
        if(
            $res >= 15
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sendMsg' => [
                        'msg'=>'check_daily_send_nums_failed',
                        'params_phone'=>$phone,
                        'params_pre'=>'daily_'.$key.'_sendSms_',
                        'daily_send_nums'=>$res,
                        'daily_send_nums_limit'=>15,
                    ],
                ])
            );
            return [
                'failed'=>true,
                'msg'=> '今日发送次数过多，请明天再试',
            ];
        }
        else{
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'sendMsg' => [
                        'msg'=>'check_daily_send_nums_succeed',
                        'params_phone'=>$phone,
                        'params_pre'=>'daily_'.$key.'_sendSms_',
                        'daily_send_nums'=>$res,
                        'daily_send_nums_limit'=>15,
                    ],
                ])
            );
        }

        $digit = OnlineGoodsUser::createRandomDigit();
        //发短信
        $res = (new AliSms())->sendByTempleteV2($phone, 'SMS_218160347',[
            'code' => $digit,
        ]);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sendMsg' => [
                    'msg'=>'send_sms',
                    'params_phone'=>$phone,
                    'params_code'=>$digit,
                    'send_sms_res'=>$res,
                ],
            ])
        );
        if(!$res){
            return [
                'failed'=>true,
                'msg'=> '短信发送失败',
            ];

        }

        //设置验证码
        OnlineGoodsUser::setRandomDigit($phone,$digit,$key.'_sms_code_');
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'sendMsg' => [
                    'msg'=>'setRandomDigit',
                    'params_phone'=>$phone,
                    'params_code'=>$digit,
                    'params_prx'=>$key.'_sms_code_',
                    'redis_res_getRandomDigit'=>OnlineGoodsUser::getRandomDigit($phone,$key.'_sms_code_'),
                ],
            ])
        );
        return [
            'success'=>true,
            'msg'=> '成功',
        ];

    }

}
