<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use EasySwoole\HttpClient\HttpClient;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class MobileCheckInfo extends ModelBase
{

    //
    protected $tableName = 'mobile_check_info_chuang_lan';



    public static function getStatusMap(){
        return ChuangLanService::getStatusCnameMap();
    }

    static  function  addRecordV2($info){
        $oldRes =  self::findByMobile(
            $info['mobile']
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
                'mobile' => $requestData['mobile'],
                'status' => $requestData['status'],
                'area' => $requestData['area']?:'',
                'numberType' => $requestData['numberType']?:'',
                'chargesStatus' => $requestData['chargesStatus'],
                'lastTime' => $requestData['lastTime'],
                'raw_return' => $requestData['raw_return']?:'',
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

    static function  checkIfIsMobile($mobileStr){
        if(preg_match("/^1[34578]\d{9}$/", $mobileStr)){
           return true;
        }
        return false;
    }

    static function  findResByMobile($mobileStr,$use_last_redis_res = true){
        $redisKey =  'chuang_lan_mobile_'.$mobileStr;
        $redisRes = self::takeResult($redisKey);
        if($use_last_redis_res){
            if(!empty($redisRes)){
                $redisArr = json_decode($redisRes,true);
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        'findResByMobile from redis ' => [
                            '$mobileStr' =>$mobileStr,
                            '$redisRes' =>$redisRes,
                            '$redisArr' =>$redisArr,
                        ]
                    ])
                );
                return  $redisArr;
            }
        }


        $dbInfo = self::findByMobileV2($mobileStr);
        if(!empty($dbInfo)){
            $dbRes = [
                "mobile"=>$dbInfo['mobile'],
                "lastTime"=>$dbInfo['lastTime'],
                "area"=>$dbInfo['area'],
                "numberType"=>$dbInfo['numberType'],
                "chargesStatus"=>$dbInfo['chargesStatus'],
                "status"=>$dbInfo['status'],
            ];
            self::storeResult($redisKey,json_encode($dbRes));
            return $dbRes;
        }
        return [];

    }

    static function storeResult($key, $result): void
    {

        Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key, $result) {
            $redis->select(CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB'));
            return $redis->setEx($key, CreateConf::getInstance()->getConf('env.coHttpCacheDay') * 86400, $result);
        });
    }

    static function takeResult($key)
    {
        return Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($key) {
            $redis->select(CreateConf::getInstance()->getConf('env.coHttpCacheRedisDB'));
            return $redis->get($key);
        });
    }

    static function reCheck($condtion,$nums = 100){
        $all = self::findAllByCondition($condtion);
        $i =  1 ;
        foreach ($all as $dataItem){
            if($i>=$nums){
                return true;
            }
            self::checkMobilesByChuangLan($dataItem['mobile'],false);
            self::findResByMobile($dataItem['mobile'],false);
            $i ++;
        }
        return true;
    }

    //简版
    static function  checkMobilesByChuangLan($mobileStr,$userCache = true){
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'checkMobilesByChuangLan $mobileStr ' => $mobileStr
//            ])
//        );
        $mobilesArr = explode(',',$mobileStr);
        //需要重新调取接口检测的号码集合
        $needsCheckMobiles =  [];
        //无效的号码集合
        $invalidMobiles =  [];
        //检测结果（新加旧）
        $newCheckRes = [];

        foreach ($mobilesArr as $mobile){
            if($mobile < 0 ){
                continue ;
            }
            //校验号码有效性 无效的号码 直接设置结果
            if( !self::checkIfIsMobile($mobile) ){
                 $invalidMobiles[] = $mobile;
                $newCheckRes[] = [
                    'mobile'=>$mobile,
                    'status'=> '',
                    'area'=> '',
                    'numberType'=> '',
                    'chargesStatus'=> 1,
                    'lastTime'=> '',
                    'remark'=> '号码无效',
                ];
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'checkMobilesByChuangLan mobile invalid  ' => $mobile
//                    ])
//                );
                 continue;
             }

            //从库里/redis里取旧的结果
            if($userCache){
                $tmpRes = self::findResByMobile($mobile);
                if(!empty($tmpRes)){
                    $newCheckRes[] = $tmpRes;
//                CommonService::getInstance()->log4PHP(
//                    json_encode([
//                        __CLASS__.__FUNCTION__ .__LINE__,
//                        'checkMobilesByChuangLan mobile has  old res    ' => [
//                            '$mobile' => $mobile,
//                            '$tmpRes' => $tmpRes,
//                        ]
//                    ])
//                );
                    continue;
                }
            }

            //之前没检测过 需要重新检测的
            $needsCheckMobiles[$mobile] = $mobile;
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    'checkMobilesByChuangLan mobile has no old res .needs check    ' => [
//                        '$mobile' => $mobile,
//                    ]
//                ])
//            );
        }

        //需要调取api拉取结果的
        if(
            !empty($needsCheckMobiles)
        ){
            $newMobileStr = join(',',$needsCheckMobiles);
            $newMobilesCheckRes = (new ChuangLanService())->getCheckPhoneStatusV2([
                'mobiles' => $newMobileStr,
            ]);
//            CommonService::getInstance()->log4PHP(
//                json_encode([
//                    __CLASS__.__FUNCTION__ .__LINE__,
//                    'checkMobilesByChuangLan mobile needs check mobiles   ' => [
//                        '$needsCheckMobiles' => $needsCheckMobiles,
//                        '$newMobilesCheckRes' => $newMobilesCheckRes,
//                    ]
//                ])
//            );
            //接口错误|或者全部是异常的
            if (empty($newMobilesCheckRes['data'])){
                foreach ($needsCheckMobiles as $needsCheckMobile){
                    $tmpRes = [
                        'mobile'=>$needsCheckMobile,
                        'status'=> 999,
                        'area'=> '',
                        'numberType'=> '',
                        'chargesStatus'=> 1,
                        'lastTime'=> '',
                        'raw_return'=> json_encode($newMobilesCheckRes) ,
                    ];
                    self::addRecordV2($tmpRes);
                    $newCheckRes[] = $tmpRes;
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            'checkMobilesByChuangLan mobile . new check return false   ' => [
//                                '$needsCheckMobile' => $needsCheckMobile,
//                            ]
//                        ])
//                    );
                }
            }
            //接口正常返回的
            else{
                foreach($newMobilesCheckRes['data'] as $dataItem){
                    $tmpRes = [
                        'mobile'=>$dataItem['mobile'],
                        'status'=> $dataItem['status'],
                        'area'=> $dataItem['area'],
                        'numberType'=> $dataItem['numberType'],
                        'chargesStatus'=> $dataItem['chargesStatus'] ,
                        'lastTime'=> $dataItem['lastTime'],
                        'raw_return'=> json_encode($newMobilesCheckRes) ,
                    ];
                    self::addRecordV2($tmpRes);
                    $newCheckRes[] = $tmpRes;
//                    CommonService::getInstance()->log4PHP(
//                        json_encode([
//                            __CLASS__.__FUNCTION__ .__LINE__,
//                            'checkMobilesByChuangLan mobile . new check return     ' => [
//                                '$needsCheckMobile' => $dataItem['mobile'],
//                                '$dataItem' => $dataItem,
//                            ]
//                        ])
//                    );
                }
            }
        }

        $returnData = self::formatReturnData($newCheckRes);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                'checkMobilesByChuangLan mobile .  return     ' => [
//                    '$newCheckRes' => $newCheckRes,
//                    '$returnData' => $returnData,
//                ]
//            ])
//        );

        return $returnData;
    }

    static function formatReturnData($datasArr){
        return [
            'code'=> 200000,
            'chargeStatus'=>1,
            'chargeCount'=>1,
            'message'=>"成功",
            "data" => $datasArr
        ];
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

    public static function findByMobile($mobile){
        $res =  MobileCheckInfo::create()
            ->where('mobile',$mobile)
            ->get();
        return $res;
    }

    public static function findByMobileV2($mobile){
        $res =  MobileCheckInfo::create()
            ->where('mobile',$mobile)
            ->get();
        return $res ?$res->toArray():[];
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
                        `mobile_check_info_chuang_lan` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
