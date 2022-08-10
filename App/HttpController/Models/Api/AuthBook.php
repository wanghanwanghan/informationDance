<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\CarInsuranceInstallment;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

class AuthBook extends ModelBase
{
    protected $tableName = 'information_dance_auth_book';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static  function  addRecordV2($info){
        $oldRecord = self::findByEntName($info['phone'],$info['entName'],$info['code'],$info['type']);
        if(
            $oldRecord
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'information_dance_auth_book has old  record'=>[
                        'ent_name'=>$info['ent_name'],
                        'user_id'=>$info['user_id'],
                    ],

                ])
            );
            return  $oldRecord->getAttr('id');
        }

        return AuthBook::addRecord(
            $info
        );
    }



    public static function addRecord($requestData){
        try {
            $res =  AuthBook::create()->data([
                'phone' => $requestData['phone'],
                'entName' => $requestData['entName'],
                'ent_name' => $requestData['ent_name'],
                'code' => $requestData['code'],
                'name' => $requestData['name'],
                'status' => $requestData['status'],
                'type' => $requestData['type'],
                'url' => $requestData['url']?:'',
                'remark' => $requestData['remark']?:'',
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

    public static function findByEntName($phone,$entName,$code,$type){
        $res =  AuthBook::create()
            ->where('phone',$phone)
            ->where('entName',$entName)
            ->where('code',$code)
            ->where('type',$type)
            ->get();
        return $res;
    }

}
