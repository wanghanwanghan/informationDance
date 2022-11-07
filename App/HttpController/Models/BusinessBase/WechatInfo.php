<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\RDS3\HdSaic\CompanyBasic;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\XinDong\XinDongService;

class WechatInfo extends ModelBase
{
    protected $tableName = 'wechat_info';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }

    static  function  addRecordV2($info){

        if(
            self::findByPhoneV3($info['phone_md5'])
        ){
            return  true;
        }

        return WechatInfo::addRecord(
            $info
        );
    }

    public static function addRecord($requestData){
        try {
            $res =  WechatInfo::create()->data([
                'code' => $requestData['code']?:'',
                'phone' => $requestData['phone'],
                'phone_md5' => $requestData['phone_md5'],
                'sex' => $requestData['sex']?:'',
                'nickname' => $requestData['nickname']?:'',
                'signature' => $requestData['signature']?:'',
                'province' => $requestData['province']?:'',
                'city' => $requestData['city']?:'',
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
        $res =  WechatInfo::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = WechatInfo::findById($id);

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
        $model = WechatInfo::create()
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
        $model = WechatInfo::create();
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
        $res =  WechatInfo::create()
            ->where('id',$id)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  WechatInfo::create()
            ->where('phone_md5',$phone)
            ->get();
        return $res;
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
    public static function findByPhoneV3($phone){
        $res =  self::findByPhone(($phone));
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
        $info = WechatInfo::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    // 用完今日余额的
    public static function findBySql($where){
        $Sql = " select *  
                            from  
                        `wechat_info` 
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
