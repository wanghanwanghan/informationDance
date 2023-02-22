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

class OnlineGoodsDaikuanBank extends ModelBase
{

    protected $tableName = 'online_goods_daikuan_bank';


    static  function  addRecordV2($info){
        $oldRes = self::findByBank($info['bank_cname']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return OnlineGoodsDaikuanBank::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){

        try {
           $res =  OnlineGoodsDaikuanBank::create()->data([
                'bank_cname' => $requestData['bank_cname'],
                'remark' => $requestData['token']?:'',
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
        $res =  OnlineGoodsDaikuanBank::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = OnlineGoodsDaikuanBank::findById($id);

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
        $model = OnlineGoodsDaikuanBank::create()
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

    public static function findByConditionV2($whereArr,$page=1,$size=20){
        $model = OnlineGoodsDaikuanBank::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }

        $model->page($page,$size)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                '置金-银行表'=>[
                    '$whereArr'=>$whereArr,
                    '$page' => $page,
                    '$size'=>$size,
                    'sql' => $model->lastQuery()->getLastQuery(),
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  OnlineGoodsDaikuanBank::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByBank($bank_cname){
        $res =  OnlineGoodsDaikuanBank::create()
            ->where('bank_cname',$bank_cname)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = OnlineGoodsDaikuanBank::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `online_goods_daikuan_bank` 
                            $where
                " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }



}
