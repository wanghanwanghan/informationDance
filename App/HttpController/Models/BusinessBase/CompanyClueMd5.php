<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\XinDong\XinDongService;

class CompanyClueMd5 extends ModelBase
{
    protected $tableName = 'company_clue_md5';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }

    static  function  addRecordV2($info){

//        if(
//            self::findByPhoneV2($info['phone'])
//        ){
//            return  true;
//        }

        return CompanyClueMd5::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
            /**
            `entname` varchar(100) NOT NULL DEFAULT '',
            `code` varchar(100) NOT NULL DEFAULT '',
            `fr` varchar(100) NOT NULL DEFAULT '',
            `qcc` text NOT NULL,
            `pub` text NOT NULL,
            `pri` text NOT NULL,
             */
            $res =  CompanyClueMd5::create()->data([
                'entname' => $requestData['entname']?:'',
                'code' => $requestData['code'],
                'fr' => $requestData['fr'],
                'qcc' => $requestData['qcc'],
                'pub' => $requestData['pub'],
                'pri' => $requestData['pri'],
                'created_at' => $requestData['created_at'],
                'updated_at' => $requestData['updated_at'],
            ])->save();

        } catch (\Throwable $e) {
            return CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'failed',
                    '$requestData' => $requestData,
                    '$e' => $e->getMessage(),
                ])
            );
        }
        return $res;
    }


    public static function findAllByCondition($whereArr){
        $res =  CompanyClueMd5::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = CompanyClueMd5::findById($id);

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

    public static function findByConditionWithCountInfo($whereArr,$page,$pageSize){
        $model = CompanyClueMd5::create()
            ->where($whereArr)
            ->page($page,$pageSize)
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
        $model = CompanyClueMd5::create();
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
        $res =  CompanyClueMd5::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  CompanyClueMd5::create()
            ->where('phone_md5',$phone)
            ->get();
        return $res;
    }

    static function daiLiJiZhang($phone){
        $dataRes = self::findByPhoneV2($phone);
        return $dataRes['label_num'];
    }

    public static function findByPhoneV2($phone){
        $res =  self::findByPhone(md5($phone));
        $resData =  $res?$res->toArray():[];
//        CommonService::getInstance()->log4PHP(
//            json_encode([
//                __CLASS__.__FUNCTION__ .__LINE__,
//                [
//                    'findWeiXinByPhone'=>[
//                        '$phone'=>$phone,
//                        'md5'=>md5($phone),
//                        '$resData'=>$resData
//                    ]
//                ]
//            ])
//        );
        return $resData;
    }

    public static function setData($id,$field,$value){
        $info = CompanyClueMd5::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `company_clue_md5` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
        return $data;
    }

    /**
     * 按地区导出
     */
    static  function  exportByDistrict($distric  = '9144'){
        $fileName = date('YmdHis').'_'.'export_wechat.csv';
        $f = fopen(TEMP_FILE_PATH.$fileName, "w");
        fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv(
            $f,
        [
            '企业名',
            '税号',
            '手机号',
            '微信',
            '姓名',
            '职位',
            '微信匹配方式',
            '微信匹配详情',
            '微信匹配得分',
        ]);

        $Sql = " select *  from     `wechat_info`  WHERE `code` LIKE  '$distric%'  limit 2000  " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
        foreach ($data as $dataItem){
            if($dataItem['code']){
                $companyRes = CompanyBasic::findByCode($dataItem['code']);
                $companyRes = $companyRes?$companyRes->toArray():[];
            }
            $phone_res = \wanghanwanghan\someUtils\control::aesDecode($dataItem['phone'], $dataItem['created_at']);
            $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($companyRes['ENTNAME'],$dataItem['nickname']);
            fputcsv($f, [
                $companyRes['ENTNAME'],
                $dataItem['code'],
                $phone_res,
                $dataItem['nickname'],
                $tmpRes['data']['stff_name'],
                $tmpRes['data']['staff_type_name'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
            ]);
        }
        return $fileName;
    }

}
