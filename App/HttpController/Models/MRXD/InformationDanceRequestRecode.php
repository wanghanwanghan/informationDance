<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class InformationDanceRequestRecode extends ModelBase
{

    protected $tableName = '';

    static  function  addRecordV2($info){
        $oldRes = self::findByPhone($info['phone']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return InformationDanceRequestRecode::addRecord(
            $info
        );
    }



    public static function addRecord($requestData){

        try {
           $res =  InformationDanceRequestRecode::create()->data($requestData)->save();

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
        $res =  InformationDanceRequestRecode::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = InformationDanceRequestRecode::findById($id);

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
        $model = InformationDanceRequestRecode::create()
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
        $model = InformationDanceRequestRecode::create();
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
        $res =  InformationDanceRequestRecode::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByToken($token){
        $res =  InformationDanceRequestRecode::create()
            ->where('token',$token)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  InformationDanceRequestRecode::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = InformationDanceRequestRecode::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    static function getStatictsData($whereConditions = []){
        $where = " 1 = 1 ";

        if( $whereConditions['userId'] > 0 ){
            $where .= " AND  userId = ".$whereConditions['userId'];
        }

        if( $whereConditions['min_date'] > 0 ){
            $where .= " AND  created_at >= ".strtotime($whereConditions['min_date']);
        }

        if( $whereConditions['max_date'] > 0 ){
            $where .= " AND  created_at <= ".strtotime($whereConditions['max_date']);
        }

        $sql = "SELECT
                    userId,
                    SUM(1) as total_num,
                    SUM(IF( `responseCode` = 200 AND spendMoney = 0 , 1, 0)) as cache_num,
                    DATE_FORMAT( FROM_UNIXTIME( `created_at` ), '%Y-%m' ) AS date_time 
                FROM
                    information_dance_request_recode_".$whereConditions['year']." 
                WHERE $where
                GROUP BY
                    userId,
                    date_time
        ";

        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账模块-统计客户请求信息-sql' => $sql,
                "参数"=>$whereConditions
            ],JSON_UNESCAPED_UNICODE)
        );

        return self::findBySql($sql);
    }

    /*****
    数据量小 客户少 直接查了 若是哪天多了起来了  再说
     */
    static function  getAllUsers(){
        $sql = "SELECT DISTINCT
                    ( userId ) 
                FROM
                    information_dance_request_recode_2021 UNION
                SELECT DISTINCT
                    ( userId ) 
                FROM
                    information_dance_request_recode_2022 UNION
                SELECT DISTINCT
                    ( userId ) 
                FROM
                    information_dance_request_recode_2023
";
        CommonService::getInstance()->log4PHP(
            json_encode([
                "对账单模块-查所有客户-sql" => $sql,
            ],JSON_UNESCAPED_UNICODE)
        );

        return self::findBySql($sql);
    }

    public static function findBySql($sql){
        $data = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }

    static  function exportData($data,$filedCname ){
        $filename = '对账单_'.date('YmdHis').'.xlsx';
        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                "对账单-导出-开始执行"=>[
                    "文件名"=>$filename,
                    "文件路径"=>TEMP_FILE_PATH,
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        $excel = new \Vtiful\Kernel\Excel($config);
        $fileObject = $excel->fileName($filename, 'sheet');
        $fileHandle = $fileObject->getHandle();

        $format = new Format($fileHandle);
        $colorStyle = $format
            ->fontColor(Format::COLOR_ORANGE)
            ->border(Format::BORDER_DASH_DOT)
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $format = new Format($fileHandle);

        $alignStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->toResource();

        $fileObject
            ->defaultFormat($colorStyle)
            ->header($filedCname)
            ->defaultFormat($alignStyle)
        ;

        $i = 1;
        foreach ($data as $dataItem){
            if( $i%300 == 0 ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '对账单-导出-次数' => $i,
                    ])
                );
            }

            $fileObject ->data([$dataItem]);
            $i ++;
        }

        $format = new Format($fileHandle);
        //单元格有\n解析成换行
        $wrapStyle = $format
            ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
            ->wrap()
            ->toResource();

        $fileObject->output();
    }

}
