<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\RedisPool\Redis;

class ConfigInfo extends ModelBase
{
    protected $tableName = 'config_info';
    static $redis_db_num = 14;

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    public static function findByName($name){
        $res =  ConfigInfo::create()
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setValue($id,$value){
        $info = ConfigInfo::findById($id);

        return $info->update([
            'value' => $value,
        ]);
    }

    public static function checkCrontabIfCanRun($crontabName){
        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);
        if(isset($configArr[$crontabName])){
            return  $configArr[$crontabName]['is_running']?false:true;
        } else{
            return  true;
        }
    }

    public static function setIsRunning($crontabName){
        CommonService::getInstance()->log4PHP(
            'setIsRunning '.$crontabName
        );

        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);

        if(empty($configArr[$crontabName])){
            $configArr[$crontabName] = [
                'start_time' => 0,
                'end_time' => 0,
                'is_running' => 0,
            ];
        }

        $configArr[$crontabName]['start_time'] = date('Y-m-d H:i:s');
        $configArr[$crontabName]['is_running'] = 1;

//        CommonService::getInstance()->log4PHP(
//            'update '.json_encode($configArr)
//        );
        return $info->update([
            'value' => json_encode($configArr),
        ]);
    }

    public static function setIsDone($crontabName){
        CommonService::getInstance()->log4PHP(
            'setIsDone '.$crontabName
        );

        $info = ConfigInfo::findByName('crontab');
        $config = $info->getAttr("value");
        $configArr = json_decode($config,true);

        if(empty($configArr[$crontabName])){
            $configArr[$crontabName] = [
                'start_time' => 0,
                'end_time' => 0,
                'is_running' => 0,
            ];
        }

        $configArr[$crontabName]['is_running'] = 0;

//        CommonService::getInstance()->log4PHP(
//            'update '.json_encode($configArr)
//        );
        return $info->update([
            'value' => json_encode($configArr),
        ]);
    }

    static function  getRedisBykey($key)
    {

        $redis = Redis::defer('redis');

        $redis->select(self::$redis_db_num);
        return $redis->get($key);

        $status = (bool)$redis->setNx($methodName, 'isRun');

        $status === false ?: $redis->expire($methodName, $ttl);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'setRedisNx strat ' ,
//                'params $methodName' => $methodName,
//                'params $ttl' => $ttl,
//                ' $status' => $status,
//            ])
//        );
        return $status;
    }

    static function  setRedisBykey($key,$value, $ttl = 300): bool
    {

        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->set($key,$value,$ttl);
    }

    static function  sAdd($key,$list)
    {

        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->sAdd($list,$key);
    }

    static function  sRem($key,$list)
    {

        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->sRem($list,$key);
    }

    static function  sMembers($list)
    {

        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->sMembers($list);
    }

    static function  Sismember($key,$list)
    {
        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->Sismember($list,$key);
    }

    static function  incrRedisBykey($key): bool
    {

        $redis = Redis::defer('redis');
        $redis->select(self::$redis_db_num);
        return $redis->incr($key);
    }

    static function  setRedisNx($methodName, $ttl = 6400): bool
    {

        $redis = Redis::defer('redis');

        $redis->select(self::$redis_db_num);

        $status = (bool)$redis->setNx($methodName, 'isRun');

        $status === false ?: $redis->expire($methodName, $ttl);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'setRedisNx strat ' ,
//                'params $methodName' => $methodName,
//                'params $ttl' => $ttl,
//                ' $status' => $status,
//            ])
//        );
        return $status;
    }

    static function  removeRedisNx($methodName): bool
    {

        $redis = Redis::defer('redis');

        $redis->select(self::$redis_db_num);
        $status = !!$redis->del($methodName);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'removeRedisNx strat ' ,
//                'params $methodName' => $methodName,
//                ' $status' => $status,
//            ])
//        );
        return $status;
    }

}
