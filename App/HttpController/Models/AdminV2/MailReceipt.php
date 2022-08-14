<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class MailReceipt extends ModelBase
{

    static  $status_init = 1 ;
    static  $status_init_cname =  '初始' ;


    static  $status_succeed = 10 ;
    static  $status_succeed_cname =  '成功' ;

    static  $status_failed = 15 ;
    static  $status_failed_cname =  '失败' ;


    protected $tableName = 'mail_receipt';


    public static function getStatusMap(){


    }

    static  function  addRecordV2($info){

        if(
            self::findByEmailId($info['email_id'],$info['to'])
        ){
            return  true;
        }

        return MailReceipt::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  MailReceipt::create()->data([
                'email_id' => $requestData['email_id'],
                'to' => $requestData['to'],
                'to_other' => $requestData['to_other']?:'',
                'insurance_id' => $requestData['insurance_id']?:'0',
                'insurance_hui_zhong_id' => $requestData['insurance_hui_zhong_id']?:'0',
                'user_id' => $requestData['user_id'],
                'from' => $requestData['from'],
                'subject' => $requestData['subject']?:'',
                 'body' => $requestData['body']?:'',
                 'status' => $requestData['status']?:'1',
                'type' => $requestData['type']?:'1',
                'reamrk' => $requestData['reamrk']?:'',
                 'raw_return' => $requestData['raw_return'],
                'date' => $requestData['date'],
               'created_at' => time(),
               'updated_at' => time(),
           ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    '$e'=>$e->getMessage()
                ])
            );
        }
        return $res;
    }

    public static function findAllByCondition($whereArr){
        $res =  MailReceipt::create()
            ->where($whereArr)
            ->all();
        return $res;
    }


    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = MailReceipt::create()
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
        $model = MailReceipt::create();
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
        $res =  MailReceipt::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    //根据保丫id获取
    public static function findByInsuranceId($id){
        $res =  MailReceipt::create()
            ->where('insurance_id',$id)
            ->all();
        return $res;
    }

    //根据汇众id获取
    public static function findByInsuranceHuiZhongId($id){
        $res =  MailReceipt::create()
            ->where('insurance_hui_zhong_id',$id)
            ->all();
        return $res;
    }

    public static function findByEmailId($email_id,$to){
        $res =  MailReceipt::create()
            ->where('email_id',$email_id)
            ->where('to',$to)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = MailReceipt::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `mail_receipt` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

}
