<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class ToolsUploadQueue extends ModelBase
{

    protected $tableName = 'tools_upload_queue';

    static  $state_init = 1;
    static  $state_init_cname =  '文件上传成功';

    static  $state_file_succeed = 10;
    static  $state_file_succeed_cname =  '内容生成成功，待下载';


    //
    public static function getStatusMap(){
        return [
            self::$state_init => self::$state_init_cname,
            self::$state_file_succeed => self::$state_file_succeed_cname,
        ];
    }

    public static function addRecord($requestData){
        try {
           $res =  ToolsUploadQueue::create()->data([
                'admin_id' => $requestData['admin_id'], //
               'upload_file_name' => $requestData['upload_file_name']?:'', //
               'upload_file_path' => $requestData['upload_file_path']?:'', //
               'download_file_name' => $requestData['download_file_name']?:'', //
               'download_file_path' => $requestData['download_file_path']?:'', //
               'params' => $requestData['params']?:'', //
               'title' => $requestData['title']?:'', //
               'type' => $requestData['type']?:'', //
               'status' => $requestData['status']?:'', //
               'remark' => $requestData['remark']?:'', //
              // 'touch_time' => $requestData['touch_time']?:'', //
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
        $oldRes =  self::findAllByAdminIdAndFielName(
            $requestData['admin_id'],
            $requestData['upload_file_name']
        );
        if($oldRes){
            return  $oldRes->getAttr('id');
        }

        return  self::addRecord($requestData);
    }

    public static function findAllByCondition($whereArr){
        $res =  ToolsUploadQueue::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = ToolsUploadQueue::create()
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
        $info = ToolsUploadQueue::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function setStatus($id,$status){
        $info = ToolsUploadQueue::findById($id);

        return $info->update([
            'status' => $status,
        ]);
    }

    //设置上传文件路径
    public static function setUploadFilePath($id,$upload_file_name,$upload_file_path){
        $info = ToolsUploadQueue::findById($id);

        return $info->update([
            'upload_file_name' => $upload_file_name,
            'upload_file_path' => $upload_file_path,
        ]);
    }

    //设置下载文件路径
    public static function setDownloadFilePath($id,$download_file_name,$download_file_path){
        $info = ToolsUploadQueue::findById($id);

        return $info->update([
            'download_file_name' => $download_file_name,
            'download_file_path' => $download_file_path,
        ]);
    }

    public static function findByConditionV2($whereArr,$page){
        $model = ToolsUploadQueue::create();
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
        $res =  ToolsUploadQueue::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }


    public static function findAllByAdminIdAndFielName($admin_id,$upload_file_name){
        $res =  ToolsUploadQueue::create()
            ->where('admin_id',$admin_id)
            ->where('upload_file_name',$upload_file_name)
            ->all();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = ToolsUploadQueue::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }
}
