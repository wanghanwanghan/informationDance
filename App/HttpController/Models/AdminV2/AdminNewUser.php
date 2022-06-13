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

    public static function findById($id){
        $res =  AdminNewUser::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function checkAccountBalance($id,$chargeMoney){
        $balance = self::getAccountBalance($id) ;
        if(
             // 余额
            $balance >= $chargeMoney
         ){
            return true;
         }
        return  CommonService::getInstance()->log4PHP(
            [
                'checkAccountBalance' => 'return false',
                '$balance' => $balance,
                '$id' => $id,
            ]
        );

    }

    public static function getAccountBalance($id){
        $res =  self::findById($id);
        return $res->getAttr('money');
    }

    public static function updateMoney($id,$money){
        $info = AdminNewUser::findById($id);

        return $info->update([
            'id' => $id,
            'money' => $money
        ]);
    }
}
