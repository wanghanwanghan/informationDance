<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class DeliverDetailsHistory extends ModelBase
{

    protected $tableName = 'deliver_details_history';

    static  $state_init = 1;
    static  $state_init_cname =  '初始';

    static  $state_succeed = 10;
    static  $state_succeed_cname =  '成功';

    static  $is_destory_no = 0;
    static  $is_destory_no_cname =  '正常';
    static  $is_destory_yes = 1;
    static  $is_destory_yes_cname =  '已删除';


    public static function getStatusMap(){
        return [
            self::$state_init => self::$state_init_cname,
        ];
    }

    public static function addRecord($requestData){
        try {
           $res =  DeliverDetailsHistory::create()->data([
                'admin_id' => $requestData['admin_id'],
                'deliver_id' => $requestData['deliver_id'],
                'ent_id' => $requestData['ent_id'],
                'entName' => $requestData['entName'],
                'remark' => $requestData['remark']?:'',
                //'total_nums' => $requestData['total_nums'],
                'status' => $requestData['status']?:self::$state_init,
                'type' => $requestData['type']?:self::$state_init,
                // 'touch_time' => $requestData['touch_time']?:'',
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

    public static function addRecordV2($requestData){
        $oldRes = self::findByDeliverId($requestData['deliver_id'],$requestData['admin_id'],$requestData['entName']);
        if($oldRes){
            return  $oldRes->getAttr('id');
        }
        return  self::addRecord($requestData);
    }

    public static function findAllByCondition($whereArr){
        $res =  DeliverDetailsHistory::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = DeliverDetailsHistory::create()
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


    public static function setTouchTime($id,$touchTime){
        $info = DeliverDetailsHistory::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function findByConditionV2($whereArr,$page,$pageSize){
        $model = DeliverDetailsHistory::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$pageSize)
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
        $res =  DeliverDetailsHistory::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByDeliverId($deliver_id,$adminId,$entName){
        $res =  DeliverDetailsHistory::create()
            ->where('deliver_id',$deliver_id)
            ->where('admin_id',$adminId)
            ->where('entName',$entName)
            ->get();
        return $res;
    }

    public static function findALLByDeliverId($deliver_id){
        $res =  DeliverDetailsHistory::create()
            ->where('deliver_id',$deliver_id)
            ->all();
        return $res;
    }
    public static function findAllByAdminIdAndEntName($admin_id,$entName){
        $res =  DeliverDetailsHistory::create()
            ->where('admin_id',$admin_id)
            ->where('entName',$entName)
            ->all();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = DeliverDetailsHistory::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}
