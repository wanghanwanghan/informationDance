<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserSoukeConfig extends ModelBase
{
    // 搜客配置
    protected $tableName = 'admin_user_souke_config';

    static  $state_init = 1;
    static  $state_init_cname =  '正常';

    static  $state_del = 5;
    static  $state_del_cname =  '已删除';

    static  $is_destory_no = 0;
    static  $is_destory_no_cname =  '正常';
    static  $is_destory_yes = 1;
    static  $is_destory_yes_cname =  '已删除';

    public static function setStatus($id,$status){
        $info = AdminUserSoukeConfig::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_del => self::$state_del_cname
        ];
    }

    static function  getAllowedFieldsArray($userId){
        $res = self::findByUser($userId);
        if(!$res){
            return ["xd_id","name"];
        }
        $fieldStr = $res->getAttr("allowed_fields");
        return json_decode($fieldStr,true);
    }

    public static function addRecord($requestData){

        try {
           $res =  AdminUserSoukeConfig::create()->data([
                'user_id' => $requestData['user_id'],
               'allowed_fields' => $requestData['allowed_fields'],
               'price' => $requestData['price'],
               'max_daily_nums' => $requestData['max_daily_nums'],
               'remark' => $requestData['remark']?:'',
                'status' => $requestData['status']?:1,
               'type' => $requestData['type']?:1,
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

        $oldRes = self::findByUser($requestData['user_id']);
        if($oldRes){
            return  $oldRes->getAttr('id');
        }
        return self::addRecord($requestData);
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserSoukeConfig::create()
            ->where($whereArr)
            ->all();
        return $res;
    }


    public static function findByUser($userId){
        $res =  AdminUserSoukeConfig::create()
            ->where([
                'user_id' => $userId,
                'status' => self::$state_init
            ])
            ->get();
        return $res;
    }


    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = AdminUserSoukeConfig::create()
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
        $info = AdminUserSoukeConfig::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function findByConditionV2($whereArr,$page){
        $model = AdminUserSoukeConfig::create();
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
        $res =  AdminUserSoukeConfig::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByAdminIdAndEntName($admin_id,$entName){
        $res =  AdminUserSoukeConfig::create()
            ->where('admin_id',$admin_id)
            ->where('entName',$entName)
            ->all();
        return $res;
    }

    public static function findByConditionV3($whereArr,$page){
        $model = AdminUserSoukeConfig::create();
        if(
            !empty($whereArr)
        ){
            foreach ($whereArr as $whereItem){
                $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
            }
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


    public static function setData($id,$field,$value){
        $info = AdminUserSoukeConfig::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}
