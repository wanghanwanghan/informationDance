<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class MobileCheckInfo extends ModelBase
{

    protected $tableName = 'mobile_check_info';

    static  $type_online_time = 1;
    static  $type_online_time_cname =  '检测在网时长';

    static  $type_online_status = 5;
    static  $type_online_status_cname =  '检测状态';

    static $source_chuang_lan  =  1;
    static $source_chuang_lan_cname  =  '创蓝';

    static  $redis_prex = 'store_mobile_check_res_';

    public static function getStatusMap(){
        return ChuangLanService::getStatusCnameMap();
    }

    static  function  addRecordV2($info){
        $oldRes =  self::findByMobileAndType(
            $info['batch'],
            $info['type']
        );
        if( $oldRes  ){
            return  self::updateById(
                $oldRes->getAttr('id'),
                $info
            );
        }

        return MobileCheckInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  MobileCheckInfo::create()->data([
                'phone' => $requestData['phone'],
                'status' => $requestData['status'],
                'type' => $requestData['type'],
                'source' => $requestData['source'],
                'raw_return' => $requestData['raw_return'],
                'remark' => $requestData['remark'],
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

    static function  checkMobilesByChuangLan($mobileStr){

    }


    static function storeResult($mobile,$resArr): void
    {
        $key = self::$redis_prex.$mobile;
        Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key,$resArr) {
            $redis->select(CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB'));
            return $redis->setEx($key, CreateConf::getInstance()->getConf('env.coHttpCacheDay') * 86400, $resArr);
        });
    }

    static function takeResult($mobile)
    {
        $key = self::$redis_prex.$mobile;
        return Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key) {
            $redis->select(CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB'));
            return $redis->get($key);
        });
    }

    public static function findAllByCondition($whereArr){
        $res =  MobileCheckInfo::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = MobileCheckInfo::findById($id);
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
        $model = MobileCheckInfo::create()
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
        $model = MobileCheckInfo::create();
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
        $res =  MobileCheckInfo::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByMobileAndType($mobile,$type){
        $res =  MobileCheckInfo::create()
            ->where('phone',$mobile)
            ->where('type',$type)
            ->get();
        return $res;
    }


    public static function setData($id,$field,$value){
        $info = MobileCheckInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `mobile_check_info` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
