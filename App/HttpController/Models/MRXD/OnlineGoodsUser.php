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

    //

    static $banlance_type_zeng_jia = 5;
    static $banlance_type_zeng_jia_cname = '增加';
    static $banlance_type_jian_shao = 10;
    static $banlance_type_jian_shao_cname = '减少';


    static  function  changeBalance($userId,$amount,$type,$remark= ''){
        $userInfo = self::findById($userId);
        $oldBalance = $userInfo->money;
        $newBalance = $oldBalance;
        if($type == self::$banlance_type_zeng_jia){
            $newBalance += $amount;
        }
        if($type == self::$banlance_type_jian_shao){
            $newBalance -= $amount;
        }
        $changeRes =  self::updateById($userId,
            [
                'id'=>$userId,
                'money'=>$newBalance,
            ]
        );

        if(!$changeRes){
            return  false;
        }

        //添加流水记录
        return OnlineGoodsAccountLiuShui::addRecordV2(
            [
                'user_id' => $userId,
                'old_balance' => $oldBalance,
                'new_balance' => $newBalance,
                'amount' => $amount?:0,
                'type' => $type,
                'remark'=>$remark,
            ]
        );
    }

    //减少金额的时候 需要
    static  function  checkBalance($userId,$amount){
        $userInfo = self::findById();
    }

    // 是否vip
    static function IsVip($userInfo){
        return $userInfo['level'] == self::$level_vip?1:0;
    }

    // 是否vip
    static function IsVipV2($id){
        $userDataModel = self::findById($id);
        $userInfo = $userDataModel->toArray();
        return  self::IsVip($userInfo);
    }

    static function  addDailySmsNumsV2($phone,$prx = "daily_online_sendSms_"){
        CommonService::getInstance()->log4PHP(
            json_encode([
                '智慧金融-验证码-日发送记录' => [
                    '手机' => $phone,
                    '前缀' => $prx,
                ],
            ],JSON_UNESCAPED_UNICODE)
        );

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
                '智慧金融-验证码-日发送记录' => [
                    '手机' => $phone,
                    '前缀' => $prx,
                    '每日次数-redis-key' => $daily_limit_key,
                    '每日次数-redis-value' => $nums,
                    '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                    '每日次数-redis-key对应的时间-redis-value' => $dates,
                ],
            ],JSON_UNESCAPED_UNICODE)
        );

        //之前没有过
        if($nums <= 0){
            //设置KEY
            $redis->set($daily_limit_key,1);
            //设置KEY的时间
            $redis->set($daily_limit_key2,date('Ymd'),60*60*24);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '智慧金融-验证码-日发送记录-是为今日第一次发送' => [
                        '手机' => $phone,
                        '前缀' => $prx,
                        '每日次数-redis-key' => $daily_limit_key,
                        '每日次数-redis-value' => $nums,
                        '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                        '每日次数-redis-key对应的时间-redis-value' => $dates,
                    ],
                ],JSON_UNESCAPED_UNICODE)
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
                    '智慧金融-验证码-日发送记录-过期了1' => [
                        '手机' => $phone,
                        '前缀' => $prx,
                        '每日次数-redis-key' => $daily_limit_key,
                        '每日次数-redis-value' => $nums,
                        '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                        '每日次数-redis-key对应的时间-redis-value' => $dates,
                    ],
                ],JSON_UNESCAPED_UNICODE)
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
                    '智慧金融-验证码-日发送记录-过期了2' => [
                        '手机' => $phone,
                        '前缀' => $prx,
                        '每日次数-redis-key' => $daily_limit_key,
                        '每日次数-redis-value' => $nums,
                        '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                        '每日次数-redis-key对应的时间-redis-value' => $dates,
                    ],
                ],JSON_UNESCAPED_UNICODE)
            );
        }

        //更新KEY
        $nums = $redis->get($daily_limit_key);
        $redis->set($daily_limit_key,$nums+1);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '智慧金融-验证码-日发送记录-更新key-value' => [
                    '手机' => $phone,
                    '前缀' => $prx,
                    '每日次数-redis-key' => $daily_limit_key,
                    '每日次数-redis-value' => $nums,
                    '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                    '每日次数-redis-key对应的时间-redis-value' => $dates,
                ],
            ],JSON_UNESCAPED_UNICODE)
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
                '智慧金融-验证码-获取日发送记录' => [
                    '手机' => $phone,
                    '前缀' => $prx,
                    '每日次数-redis-key' => $daily_limit_key,
                    '每日次数-redis-value' => $nums,
                    '每日次数-redis-key对应的时间-redis-key' => $daily_limit_key2,
                    '每日次数-redis-key对应的时间-redis-value' => $dates,
                ],
            ],JSON_UNESCAPED_UNICODE)
        );
        return $nums;
    }

    static function  setRandomDigit($phone,$digit,$prx="online_sms_code_"){
        $res = ConfigInfo::setRedisBykey($prx.$phone,$digit,600);
        CommonService::getInstance()->log4PHP(
            json_encode([
                '验证码-设置有效期-' => [
                    '手机号'=>$phone,
                    '验证码'=>$digit,
                    '前缀'=>$prx,
                    'redis-key'=>$prx.$phone,
                    'redis-res'=>$res,
                ],
            ],JSON_UNESCAPED_UNICODE)
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
