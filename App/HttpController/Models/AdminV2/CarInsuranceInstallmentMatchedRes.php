<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CodeCa16;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Models\RDS3\HdSaic\CompanyInv;
use App\HttpController\Service\ChuangLan\ChuangLanService;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\GuoPiao\GuoPiaoService;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\LongXin\LongXinService;


// use App\HttpController\Models\AdminRole;

class CarInsuranceInstallmentMatchedRes extends ModelBase
{

    protected $tableName = 'car_insurance_installment_matched_res';

    static $status_init = 1;
    static $status_init_cname = '初始化';

    static $status_matched_succeed = 5;
    static $status_matched_succeed_cname = '匹配成功';

    static $status_matched_failed = 10;
    static $status_matched_failed_cname = '匹配失败';

    static $pid_wei_shang_dai  = 1;
    static $pid_wei_shang_dai_cname  = '微商贷'  ;
    static $pid_jin_qi_dai  = 2;
    static $pid_jin_qi_dai_cname  = '金企贷'  ;
    static $pid_pu_hui_dai  = 3;
    static $pid_pu_hui_dai_cname  = '浦慧贷'  ;

     static function  getStatusMap(){
         return [
             self::$status_init=>self::$status_init_cname,
             self::$status_matched_succeed=>self::$status_matched_succeed_cname,
             self::$status_matched_failed=>self::$status_matched_failed_cname,
         ];
     }

    static  function  addRecordV2($info){

        if(
            self::findByProductId($info['product_id'],$info['car_insurance_id'])
        ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'CarInsuranceInstallmentMatchedRes has old  record'=>[
                        'product_id'=>$info['product_id'],
                        'car_insurance_id'=>$info['car_insurance_id'],
                    ],

                ])
            );
            return  true;
        }

        return CarInsuranceInstallmentMatchedRes::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
           $res =  CarInsuranceInstallmentMatchedRes::create()->data([
                'user_id' => $requestData['user_id'],
                'product_id' => $requestData['product_id']?:0,
                'msg' => $requestData['msg'],
                'name' => $requestData['name'],
               'car_insurance_id' => $requestData['car_insurance_id'],
                'status' => $requestData['status']?:1,
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


    public static function findAllByCondition($whereArr){
        $res =  CarInsuranceInstallmentMatchedRes::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = CarInsuranceInstallmentMatchedRes::findById($id);

        return $info->update([
            'touch_time' => $touchTime,
        ]);
    }

    public static function updateById(
        $id,$data
    ){
        $info = self::findById($id);
        return $info->update($data);
    }

    public static function findByConditionWithCountInfo($whereArr,$page){
        $model = CarInsuranceInstallmentMatchedRes::create()
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
        $model = CarInsuranceInstallmentMatchedRes::create();
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
        $res =  CarInsuranceInstallmentMatchedRes::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findOneByUserId($userId){
        $res =  CarInsuranceInstallmentMatchedRes::create()
            ->where('user_id',$userId)
            ->order('id', 'DESC')
            ->get();
        return $res;
    }


    public static function findByProductId($product_id,$car_insurance_id){
        $res =  CarInsuranceInstallmentMatchedRes::create()
            ->where('car_insurance_id',$car_insurance_id)
            ->where('product_id',$product_id)
            ->get();
        return $res;
    }


    public static function findByName($user_id,$name,$product_id){
        $res =  CarInsuranceInstallmentMatchedRes::create()
            ->where('name',$name)
            ->where('product_id',$product_id)
            ->where('user_id',$user_id)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = CarInsuranceInstallmentMatchedRes::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `car_insurance_installment_matched_res` 
                            $where
        ";
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


    public static function getDataLists($where,$page){
        $res =   CarInsuranceInstallmentMatchedRes::findByConditionV2(
            $where,$page
        );
        $newData = [];
        foreach ($res['data'] as &$dataItem){
            $dataArr = json_decode(
                $dataItem['post_params'],true
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataArr' => $dataArr,
                    '$dataItem'=>$dataItem,

                    'product_id'=>$dataArr['product_id'],
                    'post_params'=>$dataItem['post_params']
                ])
            );
            $dataRes = (new \App\HttpController\Service\BaoYa\BaoYaService())->getProductDetail
            (
                $dataArr['product_id']
            );

            $tmp = [
                'id'=>$dataItem['id'],
                'title' =>$dataRes['data']['title'],
                'description' =>$dataRes['data']['description'],
                'logo' =>$dataRes['data']['logo'],
                'created_at' => date('Y-m-d H:i:s',$dataItem['created_at']),
            ];
            $tmp = array_merge($tmp,$dataArr);
            $newData[] = $tmp;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$dataRes' => $dataRes
                ])
            );
        }

        return [
            'data' => $newData,
            'total' => $res['total'],
        ];
    }



}
