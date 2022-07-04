<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;

class AdminNewUser extends ModelBase
{
    protected $tableName = 'admin_new_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static  $chargeTypeAdd = 5 ;
    static  $chargeTypeAddCname =  '充值' ;

    static  $chargeTypePreAdd = 7 ;
    static  $chargeTypePreAddCname =  '预充值' ;

    static  $chargeTypeDele = 10 ;
    static  $chargeTypeDeleCname =  '扣费' ;

    public static function aesEncode($str){
        return \wanghanwanghan\someUtils\control::aesEncode($str, CreateConf::getInstance()->getConf('env.salt'));
    }

    public static function aesDecode($token){
        return \wanghanwanghan\someUtils\control::aesDecode($token);
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
    public static function findBySql($where){
        $Sql = "select * from    `admin_new_user`   $where  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        return $data;
    }
    public static function checkAccountBalance($id,$chargeMoney){

        $balance = self::getAccountBalance($id) ;
        if(
             // 余额
            $balance >= $chargeMoney
         ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'params $id ' =>$id,
                    '$balance ' =>$balance,
                    '$chargeMoney' =>$chargeMoney,
                    'return'=> true
                ])
            );
            return true;
         }
        return  CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'params $id ' =>$id,
                '$balance ' =>$balance,
                '$chargeMoney' =>$chargeMoney,
                'return'=> false
            ])
        );

    }

    public static function getAccountBalance($id){
        $res =  self::findById($id);
        $money = $res->getAttr('money');
        CommonService::getInstance()->log4PHP(
            json_encode([
                'admin new user getAccountBalance   '=> 'strat',
                '$money' =>  $money
            ])
        );
        return $money;
    }

    public static function updateMoney($id,$money){
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                ' charge   ',
                'id' => $id,
                '$money' => $money
            ])
        );
        $info = AdminNewUser::findById($id);

        return $info->update([
            'id' => $id,
            'money' => $money
        ]);
    }
    static  function  addRecordV2($info){

        if(
            self::findByPhone($info['phone'])
        ){
            return  true;
        }

        return AdminNewUser::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
            $res =  AdminNewUser::create()->data([
                'user_name' => $requestData['user_name'],
                'password' => $requestData['password'],
                'phone' => $requestData['phone'],
                'email' => $requestData['email'],
                'money' => $requestData['money']?:0,
                'company_id' => $requestData['company_id']?:0,
                'status' => $requestData['status']?:1,
                'created_at' => time(),
                'updated_at' => time(),
            ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'failed',
                    '$requestData' => $requestData
                ])
            );
        }
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  AdminUserFinanceExportDataQueue::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

}
