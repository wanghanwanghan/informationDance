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

class ShangJiFields extends ModelBase
{

    protected $tableName = 'shang_ji_fields';

    static  function  stateMaps(){

        return [

        ] ;
    }

    static  function  addRecordV2($info){

        return ShangJiFields::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  ShangJiFields::create()->data($requestData)->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'shang_ji_fields_入库失败'=>[
                        '参数' => $requestData,
                        '错误信息' => $e->getMessage(),
                    ]
                ],JSON_UNESCAPED_UNICODE)
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  ShangJiFields::create()
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
        $model = ShangJiFields::create()
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

    public static function findByConditionV2($whereArr,$page,$limit = 20 ){
        $model = ShangJiFields::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$limit)
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
        $res =  ShangJiFields::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = ShangJiFields::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function runBySql($Sql){
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    public static function findByWhere($where){
        $Sql = " select *  
                            from  
                        `shang_ji_fields` 
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
