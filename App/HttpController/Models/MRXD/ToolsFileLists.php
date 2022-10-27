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

class ToolsFileLists extends ModelBase
{

    protected $tableName = 'tools_file_lists';



    static $level_vip = 1 ;
    static $level_vip_cname =  'vip客户' ;


    static  function  addRecordV2($info){

        return ToolsFileLists::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  ToolsFileLists::create()->data([
                'admin_id' => $requestData['admin_id'],
                'file_name' => $requestData['file_name']?:'',
                'new_file_name' => $requestData['new_file_name']?:'',
                'remark' => $requestData['remark']?:'',
                'type' => $requestData['type']?:'',
                'state' => $requestData['state']?:'',
                'touch_time' => $requestData['touch_time']?:'',
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'msg' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  ToolsFileLists::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = ToolsFileLists::findById($id);

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
        $model = ToolsFileLists::create()
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
        $model = ToolsFileLists::create();
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
        $res =  ToolsFileLists::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = ToolsFileLists::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `tools_file_lists` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
