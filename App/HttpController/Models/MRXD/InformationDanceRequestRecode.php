<?php

namespace App\HttpController\Models\MRXD;

use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\DownloadSoukeHistory;
use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Models\Api\User;
use App\HttpController\Models\ModelBase;
use App\HttpController\Models\Provide\RequestApiInfo;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\Sms\AliSms;
use EasySwoole\RedisPool\Redis;
use Vtiful\Kernel\Format;

// use App\HttpController\Models\AdminRole;

class InformationDanceRequestRecode extends ModelBase
{

    protected $tableName = 'information_dance_request_recode_2023';

    function  setTableNameByYear($year){
        $this->tableName = "information_dance_request_recode_".$year;
    }

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

    public static function findByConditionV2($year,$whereArr,$page=1,$limit=20){

        $model = InformationDanceRequestRecode::create();
        $model->setTableNameByYear($year);

        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$limit)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();


        CommonService::getInstance()->log4PHP(
            json_encode([
                "请求统计表-参数" => [
                    '年度' => $year,
                    '条件' => $whereArr,
                    'page' => $page,
                    'limit' => $limit
                ],
                "请求统计表-sql" => $model->builder? $model->builder->getLastPrepareQuery():"",
            ],JSON_UNESCAPED_UNICODE)
        );

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

    /*****
    数据量小 客户少 直接查了 若是哪天多了起来了  再说
     */
    static function  getAllUsers(){
        $sql = "SELECT DISTINCT   ( userId )    FROM information_dance_request_recode_2021 UNION   SELECT DISTINCT  ( userId )   FROM  information_dance_request_recode_2022 UNION  SELECT DISTINCT   ( userId )    FROM information_dance_request_recode_2023";
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

    static function getFullDatas($requestData){
        $year = $requestData["year"];
        $table = "information_dance_request_recode_".$year;

        $whereSql = "WHERE 1=1 ";
        if($requestData["minDate"]){
            $whereSql .= " AND created_at >= ".strtotime($requestData['minDate']);
        }

        if($requestData["maxDate"]){
            $whereSql .= " AND created_at <= ".strtotime($requestData['maxDate']);
        }

        if($requestData["userId"]){
            $whereSql .= " AND userId = ".$requestData['userId'];
        }

        $offSet = ($requestData["page"] -1 )* $requestData["pageSize"];

        $sql = "SELECT * FROM $table $whereSql LIMIT $offSet, ".$requestData["pageSize"];
        $res =  self::findBySql($sql );

        CommonService::getInstance()->log4PHP(
            json_encode([
                '接口请求统计表-取第一页-sql' => $sql,
                '接口请求统计表-取第一页-总数' => count($res),
            ],JSON_UNESCAPED_UNICODE)
        );

        $sql = "SELECT count(1) as total  FROM $table $whereSql ";
        $resTotal =  self::findBySql($sql );
        CommonService::getInstance()->log4PHP(
            json_encode([
                '接口请求统计表-sql-取总数' => $sql
            ],JSON_UNESCAPED_UNICODE)
        );

        /***
        "id"=>1,
        "year"=> "2022",
        "month"=> "12",
        "day"=> "2022-12-12",
        "request_date"=> "2022-12-12 11:11:11",
        "num"=> "1",
        "if_charge_cname"=> "是",
        "unit_price"=> "10",
        "charge_money"=> "100",
        "charge_state_cname"=> "待结算",
        "real_charge_money"=> "100",
        "charge_time"=> "2022-12-12 12:12:12",
        "operator_cname"=> "隔壁老王",
        "remark"=> "今天是周五！！！！",
         ***/
        foreach ($res as &$resItem){
            $resItem["request_date"] =  date("Y-m-d H:i:s",$resItem["created_at"]);

            $resItem["if_charge_cname"] =  "是";
            if(
                $resItem["responseCode"] == 200 &&
                $resItem["spendMoney"] == 0
            ){
                $resItem["if_charge_cname"] =  "否";
            }
        }

        return [
            "data" => $res,
            "total" => $resTotal[0]['total'],
        ];
    }


    static  function  formatData($data){
        foreach ($data as &$datum){
            /**
            created_at :  1680142119
            id  :  147537
            provideApiId  :  319
            requestData   :  "{\"billingDate\":\"2023-03-30\",\"totalAmount\":\"54840.71\",\"appId\":\"294D936D3E854057ECE6719E6D2F07BE\",\"sign\":\"1020C30E2A7BDBBBEC150707BBDBE8\",\"invoiceNumber\":\"00545667\",\"time\":\"1680142118\",\"invoiceCode\":\"132002222363\"}"
            requestId :  "6866ddd6799a94501db1766fd6d90102"
            requestIp  :  "47.95.255.203"
            requestUrl :  "/provide/v1/zw/getInvoiceCheckV2"
            responseCode  :  200
            responseData :  "{\"requestId\":\"c8cd803825a44268ab34acff0fa96f03\",\"hostId\":\"https://ivs.fapiao.com/mars/api/check/invoice\",\"code\":200,\"message\":\"查验成功发票不一致\",\"sfkccs\":\"1\",\"Paging\":null,\"msg\":\"success\"}"
            spendMoney  : "0.2100"
            spendTime  :  "1.0513"
            updated_at :  1680142119
            userId  : 59
             */

            $userInfo = RequestUserInfo::findById($datum["userId"]);
            $userInfo &&   $datum["user_name"] =  $userInfo->username;

            $datum["updated_at"] && $datum["updated_at"] =  date("Y-m-d H:i:s",$datum["updated_at"] );
            $datum["created_at"] && $datum["created_at"] =  date("Y-m-d H:i:s",$datum["created_at"] );

            //请求的接口信息
            if($datum["provideApiId"]){
                $apiInfo =  RequestApiInfo::findById($datum["provideApiId"]);
                $apiInfo && $datum["provideApiName"] =  $apiInfo->name;
                $apiInfo && $datum["provideApiDesc"] =  $apiInfo->desc;
                $apiInfo && $datum["provideApiSource"] =  $apiInfo->source;
                $apiInfo && $datum["provideApiPrice"] =  $apiInfo->price;
                $apiInfo && $datum["provideApiPath"] =  $apiInfo->path;
            }

            //是否需要付费
            $datum["needs_charge"] =  0;
            $datum["needs_charge_cname"] =  "否(请求没有成功)";


            //请求是否成功
            $datum["is_success"] =  0;
            $datum["is_success_cname"] =  "否";

            if($datum["responseCode"] == 200 ){
                $datum["is_success"] =  0;
                $datum["is_success_cname"] =  "是";

                $datum["needs_charge"] =  1;
                $datum["needs_charge_cname"] =  "是";

            }

            //是否是缓存数据
            $datum["is_cached"] =  0;
            $datum["is_cached_cname"] =  "否";
            if(
                $datum["responseCode"] == 200 &&
                $datum["spendMoney"] == 0
            ){
                $datum["is_cached"] =  0;
                $datum["is_cached_cname"] =  "是";

                $datum["needs_charge"] =  0;
                $datum["needs_charge_cname"] =  "否(是缓存数据)";
            }


            //是否全为空
            //$datum["DataArr"] = json_decode($datum['responseData'],true);

            // 200
            //财务数据专属
            if($datum["provideApiId"] == 151){
                $datum["cai_wu_data_is_valid"] = 1;
                $datum["cai_wu_data_is_valid_cname"] = "是";
                foreach ($datum["DataArr"] as $caiwu_datum){
                    foreach ($caiwu_datum as $caiwu_sub_datum){
                        if(
                            $caiwu_sub_datum != "" &&
                            $caiwu_sub_datum != "0"
                        ){
                            $datum["cai_wu_data_is_valid"] = 0;
                            $datum["cai_wu_data_is_valid_cname"] = "否(返回财务数据全部为空)";

                            $datum["needs_charge"] =  0;
                            $datum["needs_charge_cname"] =  "否(返回财务数据全部为空)";

                            break;
                        }
                    }
                }
            }
        }

        return $data;
    }

    static  function exportData($data,$filename,$headerArr ){
        $config=  [
            'path' => TEMP_FILE_PATH // xlsx文件保存路径
        ];

        CommonService::getInstance()->log4PHP(
            json_encode([
                "对账单-导出-开始执行"=>[
                    "文件名"=>$filename,
                    "表头"=>$headerArr,
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
            ->header(array_values($headerArr))
            ->defaultFormat($alignStyle)
        ;

        $i = 1;
        foreach ($data as $dataItem){
            if( $i%50 == 0 ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        '对账单-导出-次数' => $i,
                        '$dataItem' => $dataItem,
                    ])
                );
            }
            $tmp = [];
            foreach ($headerArr as $key=>$cname){
                $tmp[] = $dataItem[$key];
            }
            $fileObject ->data([$tmp]);
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
