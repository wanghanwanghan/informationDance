<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\XinDong\XinDongService;

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
        return \wanghanwanghan\someUtils\control::aesDecode($token, CreateConf::getInstance()->getConf('env.salt'));
    }

    public static function findById($id){
        $res =  AdminNewUser::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function hide($num){
       return  substr_replace($num,'****',3,4);
    }

    public static function findByPhone($phone){
        $res =  AdminNewUser::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function findByUserName($user_name){
        $res =  AdminNewUser::create()
            ->where('user_name',$user_name)
            ->get();
        return $res;
    }


    public static function findByUserNameAndPwd($user_name,$password){
        $res =  AdminNewUser::create()
            ->where('user_name',$user_name)
            ->where('password',$password)
            ->get();
        return $res;
    }

    public static function findByPhoneAndPwd($phone,$password){
        $res =  AdminNewUser::create()
            ->where('phone',$phone)
            ->where('password',$password)
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
            OperatorLog::addRecord(
                [
                    'user_id' => $id,
                    'msg' =>  "余额$balance,需要收费金额$chargeMoney,是否余额充足：true" ,
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-检测账户余额',
                ]
            );
            return true;
         }

        OperatorLog::addRecord(
            [
                'user_id' => $id,
                'msg' =>  "余额$balance,需要收费金额$chargeMoney,是否余额充足：false" ,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '新后台导出财务数据-检测账户余额',
            ]
        );
        return  false;
    }

    public static function getAccountBalance($id){
        $res =  self::findById($id);
        $money = $res->getAttr('money');
        if(empty($money)){
            return  0 ;
        }

        $newMoney = AdminNewUser::aesDecode($money);
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'admin new user getAccountBalance   '=> 'strat',
//                '$money' =>  $money,
//                '$newMoney' => $newMoney,
//            ])
//        );
        return $newMoney;
    }

    public static function updateMoney($id,$money){
        $info = AdminNewUser::findById($id);
        $userData = $info->toArray();
        $res =  $info->update([
            'id' => $id,
            'money' => $money
        ]);
        OperatorLog::addRecord(
            [
                'user_id' => $id,
                'msg' => $userData['user_name'].'余额变更【从'.$userData['money'].'变更为'.$money.'】充值结果：'.$res,
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '账户金额变更',
            ]
        );
        return $res;
    }

    public static function updateMoney2($id,$money){
        $info = AdminNewUser::findById($id);
        $userData = $info->toArray();
        $res =  $info->update([
            'id' => $id,
            'testst' => $money
        ]);
        return $res;
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

    static function testtest(){
        return ['dssddsds'];
    }
}
