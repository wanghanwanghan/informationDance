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

    /**
     * 计算出两个日期之间的月份
     * @author Eric
     * @param  [type] $start_date [开始日期，如2014-03]
     * @param  [type] $end_date   [结束日期，如2015-12]
     * @param  string $explode    [年份和月份之间分隔符，此例为 - ]
     * @param  boolean $addOne    [算取完之后最后是否加一月，用于算取时间戳用]
     * @return [type]             [返回是两个月份之间所有月份字符串]
     * $start_date = "2018-11";
     * $end_date = "2019-03";
     * var_dump(dateMonths($start_date,$end_date));
     */
    static function dateMonths($start_date,$end_date,$explode='-',$addOne=false){
        //判断两个时间是不是需要调换顺序
        $start_int = strtotime($start_date);
        $end_int = strtotime($end_date);
        if($start_int > $end_int){
            $tmp = $start_date;
            $start_date = $end_date;
            $end_date = $tmp;
        }


        //结束时间月份+1，如果是13则为新年的一月份
        $start_arr = explode($explode,$start_date);
        $start_year = intval($start_arr[0]);
        $start_month = intval($start_arr[1]);


        $end_arr = explode($explode,$end_date);
        $end_year = intval($end_arr[0]);
        $end_month = intval($end_arr[1]);


        $data = array();
        $data[] = $start_date;


        $tmp_month = $start_month;
        $tmp_year = $start_year;


        //如果起止不相等，一直循环
        while (!(($tmp_month == $end_month) && ($tmp_year == $end_year))) {
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }


        if($addOne == true){
            $tmp_month ++;
            //超过十二月份，到新年的一月份
            if($tmp_month > 12){
                $tmp_month = 1;
                $tmp_year++;
            }
            $data[] = $tmp_year.$explode.str_pad($tmp_month,2,'0',STR_PAD_LEFT);
        }


        return $data;
    }

    static function getMonthStatictsDataByUserId($userId,$startMonth,$endMonth){
        $where = " userId = $userId ";

        //拆分为月份 1个月一个月的取
        
        $allMonths = self::dateMonths(
            $startMonth,
            $whereConditions['max_date']
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '对账模块-统计客户请求信息-参数'=>$whereConditions,
                '$allMonths'=>$allMonths
            ],JSON_UNESCAPED_UNICODE)
        );
        $allDatas = [];


        foreach ($allMonths as $Month){
            //取每个月的第一个id和最后一个id 根据id统计
            //本月第一天
            $beginDate = date('Y-m-01', strtotime($Month));
            $date1 = strtotime($beginDate);
            $sql00 = "SELECT
                                                id,created_at 
                                            FROM
                                                information_dance_request_recode_".$whereConditions['year']." 
                                            WHERE
                                                created_at >= $date1 
                                                LIMIT 1 ";
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '对账模块-统计客户请求信息-sql' => $sql00,
                    "参数"=>$whereConditions
                ],JSON_UNESCAPED_UNICODE)
            );

            $res1 =  self::findBySql($sql00);
            $id1 = $res1[0]["id"];

            //本月最后一天
            $endDate = date('Y-m-d', strtotime("$beginDate +1 month -1 day"));
            $date2 = strtotime($endDate);
            $sql11 = "SELECT
                                                id,created_at 
                                            FROM
                                                information_dance_request_recode_".$whereConditions['year']." 
                                            WHERE
                                                created_at >= $date2 
                                                LIMIT 1";
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '对账模块-统计客户请求信息-sql' => $sql11,
                    "参数"=>$whereConditions
                ],JSON_UNESCAPED_UNICODE)
            );
            $res2 =  self::findBySql($sql11);
            $id2 = $res2[0]["id"];

            $sql = "SELECT
                        userId,
                        SUM(1) as total_num,
                        SUM(IF( `responseCode` = 200 AND spendMoney = 0 , 1, 0)) as cache_num,
                        created_at
                        -- ,DATE_FORMAT( FROM_UNIXTIME( `created_at` ), '%Y-%m' ) AS date_time 
                    FROM
                        information_dance_request_recode_".$whereConditions['year']." 
                    WHERE $where AND id >= $id1 AND id <= $id2
                    GROUP BY
                        userId
                        -- ,date_time
                        ,DATE_FORMAT( FROM_UNIXTIME( `created_at` ), '%Y-%m' )
            ";
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '对账模块-统计客户请求信息-sql' => $sql,
                    "参数"=>$whereConditions
                ],JSON_UNESCAPED_UNICODE)
            );
            $tmpRes =  self::findBySql($sql);
            foreach ($tmpRes as $tmpResItem){
                $allDatas[] = $tmpResItem;
            }
        }

        return $allDatas;
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
