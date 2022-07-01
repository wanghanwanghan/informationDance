<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;


class AdminUserFinanceUploadRecordV3 extends ModelBase
{
    protected $tableName = 'admin_user_finance_upload_record';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at'; 
    
    static $stateInit = 1;
    static $stateInitCname =  '处理中'; //上传完毕 待解析

    static $stateParsed = 5;
    static $stateParsedCname =  '处理中';//解析完毕

    static $stateCalCulatedPrice = 10;
    static $stateCalCulatedPriceCname = '处理中';//计算价格


    static $stateTooManyPulls = 15;
    static $stateTooManyPullsCname = '每日剩余拉取次数不足';

    static $stateBalanceNotEnough = 20;
    static $stateBalanceNotEnoughCname = '余额不足';

    static $statePullApiDone1 = 25;
    static $statePullApiDone1Cname = '处理中';//拉取财务数据结束1

    static $stateNeedsConfirm = 30;
    static $stateNeedsConfirmCname = '用户确认中';


    static $stateAllSet = 40;
    static $stateAllSetCname = '处理结束，待导出';


    public static function getStatusMaps(){

        return [
            self::$stateInit => self::$stateInitCname,
            self::$stateParsed => self::$stateParsedCname,
            self::$stateCalCulatedPrice => self::$stateCalCulatedPriceCname,
            self::$stateTooManyPulls => self::$stateTooManyPullsCname,
            self::$stateBalanceNotEnough => self::$stateBalanceNotEnoughCname,
            self::$stateNeedsConfirm => self::$stateNeedsConfirmCname,
            self::$statePullApiDone1 => self::$statePullApiDone1Cname,
            self::$stateAllSet=>self::$stateAllSetCname,
        ];

    }




    public static function findByConditionV2($whereArr,$page){

        $model = AdminUserFinanceUploadRecord::create()
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
    public static function findByCondition($whereArr,$offset, $limit){
        $res =  AdminUserFinanceUploadRecord::create()
            ->where($whereArr)
            ->limit($offset, $limit)
            ->all();  
        return $res;
    }


    public static function findById($id){

        $res =  AdminUserFinanceUploadRecord::create()
            ->where('id',$id)
            ->get();

        return $res;
    }


    public static function setTouchTime($id,$touchTime){
        $info = AdminUserFinanceUploadRecord::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }


    public static function setData($id,$field,$value){
        $info = AdminUserFinanceExportDataQueue::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    //
    public static function findBySql($where){
        $Sql = "select * from    `admin_user_finance_upload_record`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        return $data;
    }



}
