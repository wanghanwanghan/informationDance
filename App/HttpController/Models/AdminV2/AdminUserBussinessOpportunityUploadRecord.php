<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class AdminUserBussinessOpportunityUploadRecord extends ModelBase
{
    protected $tableName = 'admin_user_bussiness_opportunity_upload_record';
    static  $state_init = 1;
    static  $state_init_cname =  '内容生成中';

    static $status_init = 1;
    static $status_init_cname = '处理中';

    static $status_succeed = 10;
    static $status_succeed_cname = '处理中';

    static $need_yes = 5 ;
    static $need_no = 10 ;

    public static function getStatusMap(){

        return [
            self::$state_init => self::$state_init_cname,
        ];
    }

    static  function  addRecordV2($info){

        if(
            self::findByBatch($info['batch'])
        ){
            return  true;
        }

        return AdminUserBussinessOpportunityUploadRecord::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  AdminUserBussinessOpportunityUploadRecord::create()->data([
                'user_id' => $requestData['user_id'], //
                'file_path' => $requestData['file_path'], //
                'name' => $requestData['name'], //
                'title' => $requestData['title']?:'', //
                'size' => $requestData['size']?:'', //
                'type' => $requestData['type']?:'1', //
                'fill_weixin' => $requestData['fill_weixin']?:self::$need_no,
                'pull_api' => $requestData['pull_api']?:self::$need_no,
                'split_mobile' => $requestData['split_mobile']?:self::$need_no,
                'del_empty' => $requestData['del_empty']?:self::$need_no,
                'match_by_weixin' => $requestData['match_by_weixin']?:self::$need_no,
                'get_all_field' => $requestData['get_all_field']?:self::$need_no,
                'priority' => $requestData['priority'],
                'batch' => $requestData['batch'],
                'reamrk' => $requestData['reamrk'],
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

    public static function cost(){
        $start = microtime(true);
        $startMemory = memory_get_usage();

        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'memory_use' => round((memory_get_usage()-$startMemory)/1024/1024,3).' M',
                'costs_seconds '=> number_format(microtime(true) - $start,3)
            ])
        );
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserBussinessOpportunityUploadRecord::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = AdminUserBussinessOpportunityUploadRecord::findById($id);

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
        $model = AdminUserBussinessOpportunityUploadRecord::create()
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
        $model = AdminUserBussinessOpportunityUploadRecord::create();
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
        $res =  AdminUserBussinessOpportunityUploadRecord::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = AdminUserBussinessOpportunityUploadRecord::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `admin_user_bussiness_opportunity_upload_record` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


}
