<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\MobileCheckInfo;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\BusinessBase\CompanyClue;
use App\HttpController\Models\BusinessBase\WechatInfo;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CodeEx02;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyManager;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use App\HttpController\Service\XinDong\XinDongService;
use EasySwoole\RedisPool\Redis;

// use App\HttpController\Models\AdminRole;

class ShangJi extends ModelBase
{

    protected $tableName = 'shang_ji';

    static  function  stateMaps(){

        return [

        ] ;
    }

    static  function  addRecordV2($info){

        return ShangJi::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  ShangJi::create()->data($requestData)->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'shang_ji_入库失败'=>[
                        '参数' => $requestData,
                        '错误信息' => $e->getMessage(),
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  ShangJi::create()
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
        $model = ShangJi::create()
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

    public static function findByConditionV2($whereArr,$page,$pageSize){
        $model = ShangJi::create();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '商机findByConditionV2'=>[
                    '参数' => $whereArr,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$pageSize)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();


        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '商机执行的语句'=>[
                    '参数' => $model->lastQuery()->getLastQuery(),
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        
        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }


    public static function findById($id){
        $res =  ShangJi::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByName($shang_ji_ming_cheng){
        $res =  ShangJi::create()
            ->where('shang_ji_ming_cheng',$shang_ji_ming_cheng)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = ShangJi::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function runBySql($Sql){
        $data = sqlRawV3($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'),0);
        return $data;
    }

    public static function findByWhere($where){
        $Sql = " select *  
                            from  
                        `shang_ji` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    public static function findBySql($Sql){
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
