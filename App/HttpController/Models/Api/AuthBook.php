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
                    'AuthBook' => [
                        'msg'=>'addRecordV2_has_old_record',
                        'old_id'=>$oldRecord->getAttr('id'),
                        'params_phone'=>$info['phone'],
                        'params_entName'=>$info['entName'],
                        'params_code'=>$info['code'],
                        'params_type'=>$info['type'],
                    ],
                ])
            );
            return  $oldRecord->getAttr('id');
        }

        return AuthBook::addRecord(
            $info
        );
    }



    public static function findById($id){
        $res =  AuthBook::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByIdV2($id){
        $res =  AuthBook::create()
            ->where('id',$id)
            ->get();
        return $res->toArray();
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }


    public static function addRecord($requestData){
        try {
            $res =  AuthBook::create()->data([
                'phone' => $requestData['phone'],
                'entName' => $requestData['entName'],
                'code' => $requestData['code'],
                'name' => $requestData['name'],
                'status' => $requestData['status'],
                'type' => $requestData['type'],
                'url' => $requestData['url']?:'',
                'remark' => $requestData['remark']?:'',
                'raw_return_json' => $requestData['raw_return_json']?:'',
                'created_at' => time(),
                'updated_at' => time(),
            ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    'msg' => $e->getMessage()
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
