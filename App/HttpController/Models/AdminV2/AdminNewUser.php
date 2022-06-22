<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

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



    public static function findById($id){
        $res =  AdminNewUser::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function checkAccountBalance($id,$chargeMoney){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkAccountBalance   start ' ,
                'params $id ' =>$id,
                'params $chargeMoney ' =>$chargeMoney
            ])
        );

        $balance = self::getAccountBalance($id) ;
        CommonService::getInstance()->log4PHP(
            json_encode([
                'checkAccountBalance   get $balance ' ,
                'params $id ' =>$id,
                '$balance ' =>$balance
            ])
        );
        if(
             // 余额
            $balance >= $chargeMoney
         ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'checkAccountBalance   ok  ' ,
                    'params $balance ' =>$balance,
                    'params $chargeMoney ' =>$chargeMoney,
                ])
            );
            return true;
         }
        return CommonService::getInstance()->log4PHP(
            json_encode([
                'checkAccountBalance   failed  ' ,
                'params $balance ' =>$balance,
                'params $chargeMoney ' =>$chargeMoney,
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
                'admin new user updateMoney   '=> 'strat',
                'params $id' =>  $id,
                'params $money' =>  $money
            ])
        );
        $info = AdminNewUser::findById($id);

        return $info->update([
            'id' => $id,
            'money' => $money
        ]);
    }

    // $type 5充值 10 扣钱
//    public static function charge($id,$money,$batchNo,$datas,$type = 5){
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                'admin new user charge   '=> 'start',
//                '$id' =>  $id,
//                'money' =>  $money,
//                '$batchNo' =>  $batchNo,
//                '$datas' =>  $datas,
//                '$type' =>  $type,
//            ])
//        );
//        if(
//            FinanceLog::findByBatch($batchNo)
//        ){
//            return true;
//        }
//        // 实际扣费
//        $banlance = \App\HttpController\Models\AdminV2\AdminNewUser::getAccountBalance(
//            $id
//        );
//        if(
//            $type == 5
//        ){
//            $banlance = $banlance + $money;
//        }
//
//        if(
//            $type == 10
//        ){
//            $banlance = $banlance - $money;
//        }
//
//        $res = \App\HttpController\Models\AdminV2\AdminNewUser::updateMoney(
//            $id,
//            $banlance
//        );
//        if(!$res ){
//            return CommonService::getInstance()->log4PHP(
//                json_encode([
//                    'admin new user charge   '=> 'failed',
//                    '$res' =>  $res
//                ])
//            );
//        }
//
//        return FinanceLog::addRecordV2(
//            $datas
//        );
//    }
}
