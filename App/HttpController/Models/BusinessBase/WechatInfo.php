<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class WechatInfo extends ModelBase
{
    protected $tableName = 'wechat_info';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }

    static  function  addRecordV2($info){

        if(
            self::findByPhone($info['phone'])
        ){
            return  true;
        }

        return WechatInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
            $res =  WechatInfo::create()->data([
                'upload_record_id' => $requestData['upload_record_id'],
                'status' => $requestData['status']?:1,
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
        $res =  WechatInfo::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = WechatInfo::findById($id);

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
        $model = WechatInfo::create()
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
        $model = WechatInfo::create();
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
        $res =  WechatInfo::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  WechatInfo::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function findByPhoneV2($phone){
        $res =  self::findByPhone($phone);
        return $res?$res->toArray():[];
    }

    public static function setData($id,$field,$value){
        $info = WechatInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `wechat_info` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
        return $data;
    }


}
