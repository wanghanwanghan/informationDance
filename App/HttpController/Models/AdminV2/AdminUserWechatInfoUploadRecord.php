<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserWechatInfoUploadRecord extends ModelBase
{
    protected $tableName = 'admin_user_wechat_info_upload_record';

    static $status_init = 1;
    static $status_init_cname = '初始';

    //入库成功
    static $status_success = 5;
    static $status_success_cname = '成功';

    static $need_yes = 5 ;
    static $need_no = 10 ;

    public static function getStatusMap(){

        return [
            self::$status_init =>'待处理',
            self::$status_check_mobile_success =>'处理中',
            self::$status_split_success => '成功',
        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByName($info['name'],$info['user_id'])
        ){
            return  true;
        }

        return AdminUserWechatInfoUploadRecord::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  AdminUserWechatInfoUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], //
                'file_path' => $requestData['file_path'], //
                'name' => $requestData['name'], //
                'new_name' => $requestData['new_name']?:'', //
                'title' => $requestData['title']?:'', //
                'size' => $requestData['size']?:'', //
                'type' => $requestData['type']?:'1', //
                'batch' => $requestData['batch'], //
                'reamrk' => $requestData['reamrk'], //
                'status' => $requestData['status']?:self::$status_init,
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
        $res =  AdminUserWechatInfoUploadRecord::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserWechatInfoUploadRecord::findById($id);

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
        $model = AdminUserWechatInfoUploadRecord::create()
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
        $model = AdminUserWechatInfoUploadRecord::create();
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
        $res =  AdminUserWechatInfoUploadRecord::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByName($name,$user_id){
        $res =  AdminUserWechatInfoUploadRecord::create()
            ->where('user_id',$user_id)
            ->where('name',$name)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = AdminUserWechatInfoUploadRecord::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *      from    `admin_user_wechat_info_upload_record`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
