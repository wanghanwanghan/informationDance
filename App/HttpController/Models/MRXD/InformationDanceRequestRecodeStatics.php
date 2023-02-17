<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\Api\Statistics;
use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class InformationDanceRequestRecodeStatics extends ModelBase
{

    protected $tableName = 'information_dance_request_recode_statics';

    static  function  addRecordV2($info){
        $oldRes = self::findByUserAndMonth($info['userId'],$info["year"],$info['month']);
        if(
            $oldRes
        ){
            return  $oldRes->getAttr('id');
        }

        return InformationDanceRequestRecodeStatics::addRecord(
            $info
        );
    }



    public static function addRecord($requestData){

        try {
           $res =  InformationDanceRequestRecodeStatics::create()->data($requestData)->save();

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
        $res =  InformationDanceRequestRecodeStatics::create()
            ->where($whereArr)
            ->all();
        return $res;
    }

    public static function setTouchTime($id,$touchTime){
        $info = InformationDanceRequestRecodeStatics::findById($id);

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

    public static function findByConditionWithCountInfo($whereArr,$page = 1,$limit = 20 ){
        $model = InformationDanceRequestRecodeStatics::create()
                ->where($whereArr)
                ->page($page,$limit)
                ->order('id', 'DESC')
                ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV2($whereArr,$page =1 ,$limit = 20){
        $model = InformationDanceRequestRecodeStatics::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$limit)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'information_dance_request_recode_statics执行的语句'=>[
                    'sql' => $model->lastQuery()->getLastQuery(),
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        return [
            'data' => $res,
            'total' =>$total,
        ];
    }


    public static function findById($id){
        $res =  InformationDanceRequestRecodeStatics::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function findByUserAndMonth($userId,$year,$month){
        $res =  InformationDanceRequestRecodeStatics::create()
            ->where('userId',$userId)
            ->where('year',$year)
            ->where('month',$month)
            ->get();
        return $res;
    }

    public static function findByToken($token){
        $res =  InformationDanceRequestRecodeStatics::create()
            ->where('token',$token)
            ->get();
        return $res;
    }

    public static function findByPhone($phone){
        $res =  InformationDanceRequestRecodeStatics::create()
            ->where('phone',$phone)
            ->get();
        return $res;
    }

    public static function setData($id,$field,$value){
        $info = InformationDanceRequestRecodeStatics::findById($id);
        return $info->update([
            "$field" => $value,
        ]);
    }

    public static function findBySql($sql){
        $data = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }


    static function addStaticRecordByYear($year){
        $t1 = microtime(true);  ;

        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账模块-添加中间表统计数据-开始执行-年度' => $year
            ],JSON_UNESCAPED_UNICODE)
        );

        $monthLists = [
            $year."-01",
            $year."-02",
            $year."-03",
            $year."-04",
            $year."-05",
            $year."-06",
            $year."-07",
            $year."-08",
            $year."-09",
            $year."-10",
            $year."-11",
            $year."-12",
        ];

        $allUsers = InformationDanceRequestRecode::findBySql("SELECT   DISTINCT( userId ) as userId  FROM  information_dance_request_recode_$year");
        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账模块-添加中间表统计数据-获取所有用户' => [
                    "年度"=>$year,
                    "用户数量"=>count($allUsers),
                    "耗时"=>'耗时'.round(microtime(true)-$t1,3).'秒',
                ]
            ],JSON_UNESCAPED_UNICODE)
        );

        foreach ($monthLists as $Month){
            foreach ($allUsers as $User){
                //取每个月的第一个id和最后一个id 根据id统计
                //本月第一天
                $beginDate = date('Y-m-01', strtotime($Month));
                //本月最后一天
                $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));

                $sql = "SELECT
                            userId,
                            SUM(1) as total_num,
                            SUM(IF( `responseCode` = 200 AND spendMoney = 0 , 1, 0)) as cache_num 
                        FROM
                            information_dance_request_recode_".$year." 
                        WHERE userId = ".$User["userId"]."  AND created_at >= ".strtotime($beginDate)." AND created_at < ".strtotime($endDate)." 
                ";
                $Res =  self::findBySql($sql);
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        '对账模块-添加中间表统计数据-sql直接统计用户本月数据' =>  [
                            "sql"=>$sql,
                            "月度"=>$Month,
                            "该月第一天"=>$beginDate,
                            "该月最后一天"=>$endDate,
                            "用户"=>$User["userId"],
                            "结果数量"=>count($Res),
                            "耗时"=>'耗时'.round(microtime(true)-$t1,3).'秒',
                        ]
                    ],JSON_UNESCAPED_UNICODE)
                );
                foreach ($Res as $ResItem){
                    self::addRecordV2(
                        [
                            "userId"=>$User["userId"],
                            "year"=>$year,
                            "month"=>$Month,
                            //"day"=>$day,
                            "total_num"=>$ResItem["total_num"],
                            "total_cache_num"=>$ResItem["cache_num"],
                        ]
                    );
                }
            }
        }

        return true;
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
