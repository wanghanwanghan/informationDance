<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class DownloadSoukeHistory extends ModelBase
{

    protected $tableName = 'download_souke_history';

    static  $state_init = 1;
    static  $state_init_cname =  '初始';

    static  $state_file_succeed = 10;
    static  $state_file_succeed_cname =  '文件生成成功';

    static  $state_delivering = 20;
    static  $state_delivering_cname =  '已确认使用该名单';

    static  $state_succeed = 30;
    static  $state_succeed_cname =  '交付完成';


    static  $is_destory_no = 0;
    static  $is_destory_no_cname =  '正常';
    static  $is_destory_yes = 1;
    static  $is_destory_yes_cname =  '已删除';



    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
            self::$state_del => self::$state_del_cname
        ];
    }

    public static function addRecord($requestData){
        try {
           $res =  DeliverHistory::create()->data([
                'admin_id' => $requestData['admin_id'],
               'entName' => $requestData['entName'],
               'feature' => $requestData['feature'],
               'title' => $requestData['title'],
               'file_name' => $requestData['file_name']?:'',
               'file_path' => $requestData['file_path']?:'',
               'remark' => $requestData['remark']?:'',
               'total_nums' => $requestData['total_nums'],
                'status' => $requestData['status']?:1,
               'type' => $requestData['type']?:1,
               'is_destroy' => $requestData['is_destroy']?:0,
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

    public static function findAllByCondition($whereArr){
        $res =  DeliverHistory::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = DeliverHistory::create()
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
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function findByConditionV2($whereArr,$page){
        $model = DeliverHistory::create();
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
        $res =  DeliverHistory::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findAllByAdminIdAndEntName($admin_id,$entName){
        $res =  DeliverHistory::create()
            ->where('admin_id',$admin_id)
            ->where('entName',$entName)
            ->all();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = DeliverHistory::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}