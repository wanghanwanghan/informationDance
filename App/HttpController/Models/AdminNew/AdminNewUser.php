<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class AdminNewUser extends ModelBase
{
    protected $tableName = 'admin_new_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';


    public static function updateStatusById(
        $id,$status
    ){
        $info = AdminNewUser::create()->where('id',$id)->get();
        return $info->update([
            'id' => $id,
            'status' => $status,
        ]);
    }

    static  function  addRecordV2($info){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'AdminUserFinanceExportDataQueue  addRecordV2' ,
                " WHERE upload_record_id = ".$info['upload_record_id'].
                "  AND status = ".AdminNewUser::$state_init,
                $info
            ])
        );

        if(
            self::findByBatch($info['batch'])
//            AdminUserFinanceExportDataQueue::findBySql(
//                " WHERE upload_record_id = ".$info['upload_record_id'].
//                "  AND status = ".AdminUserFinanceExportDataQueue::$state_init
//            )
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceExportDataQueue  addRecordV2 exists' ,
                    " WHERE upload_record_id = ".$info['upload_record_id'].
                    "  AND status = ".AdminNewUser::$state_init,
                    $info
                ])
            );
            return  true;
        }

        return AdminNewUser::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
            $res =  AdminNewUser::create()->data([
                'phone' => $requestData['phone'],
                'user_name' => $requestData['user_name'],
                'password' => $requestData['password'],
                'email' => $requestData['email'],
                'money' => $requestData['money']?:'',
                'company_id' => $requestData['company_id']?:'',
                'status' => $requestData['status']?:1,
            ])->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminNewUser sql err',
                    $e->getMessage(),
                ])
            );
        }

        return $res;
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminNewUser::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();
        return $res;
    }

    public static function findById($id){
        $res =  AdminNewUser::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  AdminNewUser::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setMoney($id,$money){
        $info = self::findById($id);

        return $info->update([
            'touch_time' => $money,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `admin_new_user` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
