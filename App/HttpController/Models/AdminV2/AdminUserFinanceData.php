<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;


// use App\HttpController\Models\AdminRole;

class AdminUserFinanceData extends ModelBase
{
    /*
    
        该用户具体客户名单的收费
    */
    protected $tableName = 'admin_user_finance_data';
    static $pullFinanceTimeInterval = 31104000;
    static $pullFinanceTimeIntervalCname = '我们从供应商拉取财务数据的时间间隔';
    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    static $priceTytpeAnnually = 5;
    static $priceTytpeAnnuallyCname = '包年';
    static $priceTytpeNormal = 10;

    static $statusinit = 1;
    static $statusinitCname = '初始';

    static $statusNeedsConfirm = 5;
    static $statusNeedsConfirmCname = '待确认';

    static $statusConfirmedYes = 10;
    static $statusConfirmedYesCname = '已确认需要';

    static $statusConfirmedNo = 15;
    static $statusConfirmedNoCname = '已确认不需要';


    public static function getStatusCname(){

        return [
            self::$statusinit => self::$statusinitCname,
            self::$statusNeedsConfirm => self::$statusNeedsConfirmCname,
            self::$statusConfirmedYes => self::$statusConfirmedYesCname,
            self::$statusConfirmedNo => self::$statusConfirmedNoCname,
        ];
    }

    public static function addRecord($requestData){ 
        try {
            $data =[
                'user_id' => $requestData['user_id'],
                'entName' => $requestData['entName'],
                'year' => $requestData['year'],
                'finance_data_id' => $requestData['finance_data_id']?:0,
                'price' => $requestData['price']?:0,
                'price_type' => $requestData['price_type']?:0,
                'cache_end_date' => $requestData['cache_end_date']?:0,
                'reamrk' => $requestData['reamrk']?:'',
                'status' => $requestData['status']?:1,
            ];
           $res =  AdminUserFinanceData::create()->data($data)->save();

        } catch (\Throwable $e) {
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'error . addRecord error . $data ='=>$data
                ])
            );
        }  

        return $res;
    }


    //check balance
    public static function checkBalance($id,$financeConifgArr){
        CommonService::getInstance()->log4PHP(
            'calculatePrice start  '.$id. '  conf '.json_encode($financeConifgArr)
        );
        $res =  AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();

        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = self::getChagrgeDetailsAnnually(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '包年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsAnnuallyRes)
        );
        // 是年度收费
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err1  update price error  IsAnnually '.$id
                );
            }
        }

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            '按年    '.$id.' '. $res->getAttr('year'). '  conf '.json_encode($chagrgeDetailsByYearsRes)
        );
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsByYearsRes['YearPrice'],
                self::$priceTytpeAnnually
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err2  update price error  ChargeByYear '.$id
                );
            }
        }

        return true;
    }

    // 计算单价
    public static function calculatePrice($id,$financeConifgArr){
        CommonService::getInstance()->log4PHP(
           json_encode(
               [
                   'calculatePrice start  ',
                   $id,
                   $financeConifgArr
               ]
           )
        );

        $res =  AdminUserFinanceData::findById($id);

        //收费方式一：包年
        $chagrgeDetailsAnnuallyRes = self::getChagrgeDetailsAnnually(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
           json_encode(
               [
                   'calculatePrice  getChagrgeDetailsAnnually   ',
                   $res->getAttr('year'),
                   $financeConifgArr,
                   $res->getAttr('user_id'),
                   $res->getAttr('entName'),
                   $chagrgeDetailsAnnuallyRes
               ]
           )
        );
        // 是年度收费
        if($chagrgeDetailsAnnuallyRes['IsAnnually']){  
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                self::$priceTytpeAnnually
            );
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'calculatePrice  getChagrgeDetailsAnnually   updatePrice ',
                        $id,
                        $chagrgeDetailsAnnuallyRes['AnnuallyPrice'],
                        self::$priceTytpeAnnually
                    ]
                )
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err1  update price error  IsAnnually '.$id
                );
            }
        }  

        //收费方式二：按年
        $chagrgeDetailsByYearsRes = self::getChagrgeDetailsByYear(
            $res->getAttr('year'),
            $financeConifgArr,
            $res->getAttr('user_id'),
            $res->getAttr('entName')
        ) ;
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'calculatePrice  getChagrgeDetailsByYear    ',
                    $res->getAttr('year'),
                    $financeConifgArr,
                    $res->getAttr('user_id'),
                    $res->getAttr('entName'),
                    $chagrgeDetailsByYearsRes
                ]
            )
        );
        if($chagrgeDetailsByYearsRes['IsChargeByYear']){
            $updateRes = self::updatePrice(
                $id,
                $chagrgeDetailsByYearsRes['YearPrice'],
                self::$priceTytpeAnnually
            );
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'calculatePrice  getChagrgeDetailsByYear  updatePrice  ',
                        $id,
                        $chagrgeDetailsByYearsRes['YearPrice'],
                        self::$priceTytpeAnnually
                    ]
                )
            );
            if(!$updateRes){
                return CommonService::getInstance()->log4PHP(
                    'calculatePrice err2  update price error  ChargeByYear '.$id
                );
            }
        } 

        return true; 
    }

    public static function getFinanceDataSourceDetail($adminFinanceDataId){

        // $adminFinanceDataId 上次拉取时间| 没超过一年 就不拉
        $financeData =  AdminUserFinanceData::findById($adminFinanceDataId)
            ->toArray();
        CommonService::getInstance()->log4PHP(
            [
                'admin finance data getFinanceDataSourceDetail start ',
                'params $adminFinanceDataId' => $adminFinanceDataId,
                '$financeDataRes ' => $financeData,
            ]
        );

        // 从财务数据表取数据
        $realFinanceDataRes = NewFinanceData::findByEntAndYear(
            $financeData['entName'],$financeData['year']
        );
        if(!$realFinanceDataRes){
            return [
                'pullFromApi' => true,
                'pullFromDb' => false,
                'NewFinanceDataId' => 0,
                'NewFinanceData' => []
            ];
        }

        if(
            (strtotime($financeData['last_pull_api_date']) -time()) > self::$pullFinanceTimeInterval
        ){
            CommonService::getInstance()->log4PHP(
                [
                    'admin finance data last_pull_api_date  too long ',
                    'params last_pull_api_date' => $financeData['last_pull_api_date'],
                    '$pullFinanceTimeInterval ' =>  self::$pullFinanceTimeInterval,
                ]
            );
            return [
                'pullFromApi' => true,
                'pullFromDb' => false,
                'NewFinanceDataId' =>$realFinanceDataRes->getAttr('id'),
                'NewFinanceData' =>$realFinanceDataRes->toArray()
            ];
        }

        return [
            'pullFromApi' => false,
            'pullFromDb' => true,
            'NewFinanceDataId' => $realFinanceDataRes->getAttr('id'),
            'NewFinanceData' => $realFinanceDataRes->toArray()
        ];
    }

    //我们拉取运营商的时间间隔  
    //客户导出的时间间隔  
    public static function pullFinanceData($id,$financeConifgArr){
        $financeData =  AdminUserFinanceData::findById($id)->toArray();

        if($financeData['year'] > date('Y')){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'params $FinanceDataId ' =>$id,
                    '$financeConifgArr ' =>$financeConifgArr,
                    'year too large ,return true.'
                ])
            );
            return  true;
        }

        $postData = [
            'entName' => $financeData['entName'],
            'code' => '',
            'beginYear' => date('Y'),
            'dataCount' => 10,//取最近几年的
        ];

        // 根据缓存期和上次拉取财务数据时间 决定是取db还是取api
        $getFinanceDataSourceDetailRes = self::getFinanceDataSourceDetail($id);
        OperatorLog::addRecord(
            [
                'user_id' => $financeData['user_id'],
                'msg' =>  "企业:".$financeData['entName']." 从db还是从api取财务数据?: ".json_encode($getFinanceDataSourceDetailRes),
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '新后台导出财务数据-从db还是从api取财务数据?',
            ]
        );

        //需要从APi拉取
        if($getFinanceDataSourceDetailRes['pullFromApi']){

            $res = (new LongXinService())->getFinanceData($postData, false);
            $resData = $res['result']['data'];
            $resOtherData = $res['result']['otherData'];
            OperatorLog::addRecord(
                [
                    'user_id' => $financeData['user_id'],
                    'msg' =>  "企业:".$financeData['entName']." 从APi拉取财务数据: 参数：".json_encode($postData)." 返回：".json_encode($res),
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-从APi拉取财务数据',
                ]
            );

            //更新拉取时间
            self::updateLastPullDate($id,date('Y-m-d H:i:s'));

            // 保存到db
            foreach ($resData as $yearItem => $resDataItem){
                $dbDataArr = $resDataItem;
                $dbDataArr['entName'] = $financeData['entName'];
                $dbDataArr['year'] = $yearItem;
                $dbDataArr['raw_return'] = @json_encode($res);
                $addRes = NewFinanceData::addRecordV2($dbDataArr);
                if(!$addRes){
                    OperatorLog::addRecord(
                        [
                            'user_id' => $financeData['user_id'],
                            'msg' =>  "企业:".$financeData['entName']." 从APi拉取财务数据后,保存失败 ：表：new_finance_data 数据：".json_encode($dbDataArr),
                            'details' =>json_encode( XinDongService::trace()),
                            'type_cname' => '新后台导出财务数据-从APi拉取财务数据后,保存失败',
                        ]
                    );

                    return  false;
                }

                //不是他需要的年份
                if($financeData['year'] == $yearItem ){
                    $NewFinanceDataId = $addRes;
                    $NewFinanceData = $dbDataArr;
                }
            }
        }
        else{

            $NewFinanceDataId = $getFinanceDataSourceDetailRes['NewFinanceDataId'];
            $NewFinanceData =$getFinanceDataSourceDetailRes['NewFinanceData'] ;
        }

        //把$NewFinanceDataId更新到表
        self::updateNewFinanceDataId($id,$NewFinanceDataId);

        //设置是否需要确认
        $status = self::getConfirmStatus($financeConifgArr,$NewFinanceData);

        //如果是有需要确认的 包年内的都需要确认一边 无论数据全不全
        $configedAnnuallyYears = json_decode($financeConifgArr['annually_years'],true);
        if(
            $status  == self::$statusNeedsConfirm  &&
            !empty($configedAnnuallyYears) &&
            count($configedAnnuallyYears) >1 &&
            in_array($financeData['year'],$configedAnnuallyYears)
        ){
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' => @json_encode(
                        [
                            '财务数据状态$status'=>$status,
                            '包年$configedAnnuallyYears'=>$configedAnnuallyYears,
                            '当前年'=>$financeData['year']
                        ]
                    ),
                    'details' => json_encode(XinDongService::trace()),
                    'type_cname' => '包年内有需要确认的-需要全部确认一遍-'.$financeData['entName'],
                ]
            );
            self::changeNoNeedsConfirmToNeedsConfirm($financeData,$financeConifgArr);
        }else{
            OperatorLog::addRecord(
                [
                    'user_id' => 0,
                    'msg' => @json_encode(
                        [
                            '财务数据状态$status'=>$status,
                            '包年$configedAnnuallyYears'=>$configedAnnuallyYears,
                            '当前年'=>$financeData['year']
                        ]
                    ),
                    'details' => json_encode(XinDongService::trace()),
                    'type_cname' => '包年内有需要确认的-不需要全部确认一遍-'.$financeData['entName'],
                ]
            );
        }
        //之前没确认过的
        if(
            !in_array( $financeData['status'],[
                self::$statusConfirmedYes,
                self::$statusConfirmedNo
            ])
        ){
            self::updateStatus($id,$status);
            if(
                $status == self::$statusNeedsConfirm
            ){
                self::updateNeedsConfirm($id,1);
            }
        }

        return true;
    }

    //将包年内不需要确认的  变更为需要确认
    static  function  changeNoNeedsConfirmToNeedsConfirm($financeData,$financeConifgArr){
        $configedAnnuallyYears = json_decode($financeConifgArr['annually_years'],true);
        //$configedAnnuallyYears = $financeConifgArr['annually_years'];
        $needSetYears = "(".join(',',$configedAnnuallyYears).")";
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'changeNoNeedsConfirmToNeedsConfirm' =>[
                    $financeConifgArr['annually_years'],
                    json_decode($financeConifgArr['annually_years'],true),
                ],
                '$needSetYears'=>$needSetYears
            ])
        );
        //对应的上传数据
        $AdminUserFinanceUploadDataRecordRes = AdminUserFinanceUploadDataRecord::findByUserFinanceDataId(
            $financeData['id']
        );
        $AdminUserFinanceUploadDataRecordRes = $AdminUserFinanceUploadDataRecordRes->toArray();

        //该批次所有的数据
        $allUploadDataRecordRes = AdminUserFinanceUploadDataRecord::findByConditionV2(
            [
                'record_id' => $AdminUserFinanceUploadDataRecordRes['record_id']
            ]
        );

        $allFinanceDataIds = array_column($allUploadDataRecordRes,'user_finance_data_id');
        $needSetIds = "(".join(',',$allFinanceDataIds).")";

        $sql = " WHERE  
                        id in ($needSetIds) AND
                        entName = '".$financeData['entName']."' AND 
                        year in $needSetYears AND 
                        needs_confirm = 0 AND 
                        status = ".self::$statusConfirmedYes."   
                    ";
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ .__LINE__,
                'changeNoNeedsConfirmToNeedsConfirm $sql' =>$sql
            ])
        );
//        $allFinanceDatasNew =self::findBySql($sql);
//
//        foreach ($allFinanceDatasNew as $allFinanceDatasNewSub){
//            self::updateStatus($allFinanceDatasNewSub['id'],self::$statusNeedsConfirm);
//            self::updateNeedsConfirm($allFinanceDatasNewSub['id'],1);
//            OperatorLog::addRecord(
//                [
//                    'user_id' => 0,
//                    'msg' => @json_encode([
//                        'year'=>$allFinanceDatasNewSub['year'],
//                        'finane_data_id'=>$allFinanceDatasNewSub['id']
//                    ]),
//                    'details' => json_encode(XinDongService::trace()),
//                    'type_cname' => '财务-包年内有需要确认的-全部设置为不需要-'.$financeData['entName'],
//                ]
//            );
//        }
    }

    public static function pullFinanceDataV2($id,$financeConifgArr){
        $financeData =  AdminUserFinanceData::findById($id)->toArray();

        if($financeData['year'] > date('Y')){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ ,
                    'params $FinanceDataId ' =>$id,
                    '$financeConifgArr ' =>$financeConifgArr,
                    'year too large ,return true.'
                ])
            );
            return  true;
        }

        $postData = [
            'entName' => $financeData['entName'],
            'code' => '',
            'beginYear' => date('Y'),
            'dataCount' => 10,//取最近几年的
        ];

        // 根据缓存期和上次拉取财务数据时间 决定是取db还是取api
        $getFinanceDataSourceDetailRes = self::getFinanceDataSourceDetail($id);
        OperatorLog::addRecord(
            [
                'user_id' => $financeData['user_id'],
                'msg' =>  "企业:".$financeData['entName']." 从db还是从api取财务数据?: ".json_encode($getFinanceDataSourceDetailRes),
                'details' =>json_encode( XinDongService::trace()),
                'type_cname' => '新后台导出财务数据-从db还是从api取财务数据?',
            ]
        );

        //需要从APi拉取
        if($getFinanceDataSourceDetailRes['pullFromApi']){

            $res = (new LongXinService())->getFinanceData($postData, false);
            $resData = $res['result']['data'];
            $resOtherData = $res['result']['otherData'];
            OperatorLog::addRecord(
                [
                    'user_id' => $financeData['user_id'],
                    'msg' =>  "企业:".$financeData['entName']." 从APi拉取财务数据: 参数：".json_encode($postData)." 返回：".json_encode($res),
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-从APi拉取财务数据',
                ]
            );

            //更新拉取时间
            self::updateLastPullDate($id,date('Y-m-d H:i:s'));

            // 保存到db
            foreach ($resData as $yearItem => $resDataItem){
                $dbDataArr = $resDataItem;
                $dbDataArr['entName'] = $financeData['entName'];
                $dbDataArr['year'] = $yearItem;
                $dbDataArr['raw_return'] = @json_encode($res);
                $addRes = NewFinanceData::addRecordV2($dbDataArr);
//                if(!$addRes){
//                    OperatorLog::addRecord(
//                        [
//                            'user_id' => $financeData['user_id'],
//                            'msg' =>  "企业:".$financeData['entName']." 从APi拉取财务数据后,保存失败 ：表：new_finance_data 数据：".json_encode($dbDataArr),
//                            'details' =>json_encode( XinDongService::trace()),
//                            'type_cname' => '新后台导出财务数据-从APi拉取财务数据后,保存失败',
//                        ]
//                    );
//
//                    return  false;
//                }

                //不是他需要的年份
                if($financeData['year'] == $yearItem ){
                    $NewFinanceDataId = $addRes;
                    $NewFinanceData = $dbDataArr;
                }
            }
        }
        else{

            $NewFinanceDataId = $getFinanceDataSourceDetailRes['NewFinanceDataId'];
            $NewFinanceData =$getFinanceDataSourceDetailRes['NewFinanceData'] ;
        }

        //把$NewFinanceDataId更新到表
        self::updateNewFinanceDataId($id,$NewFinanceDataId);

        //设置是否需要确认
        //设置是否需要确认
        $status = self::getConfirmStatus($financeConifgArr,$NewFinanceData);

        return [
            'res'=>'succeed',
            'status'=>$status
        ];
    }
    public  static  function getConfirmStatus($financeConifgArr,$dataItem){

        // 不需要确认的 全部为需要的
        if(!$financeConifgArr['needs_confirm']){
            return self::$statusConfirmedYes;
        }


        $needsConfirmFields = json_decode($financeConifgArr['allowed_fields'],true);
        //全部为空的时候 不需要
        $hasValidData = false;
        foreach ($needsConfirmFields as  $field){
            if(
                $dataItem[$field] >  0
            ){
                $hasValidData = true;
                break;
            }
        }
        if(!$hasValidData){
            //return self::$statusConfirmedNo;
        }

        //有的为空 需要确认
        foreach ($dataItem as $itemKey => $value){
            if(
                in_array($itemKey,$needsConfirmFields) &&
                empty($value)
            ){
                return self::$statusNeedsConfirm;
            }
        }

//        foreach ($dataItem as $itemKey => $value){
//            if(
//                in_array($itemKey,$needsConfirmFields) &&
//                empty($value)
//            ){
//                return self::$statusNeedsConfirm;
//            }
//        }

        return self::$statusConfirmedYes;
    }
    public static function getChagrgeDetailsAnnually(
        $year,$financeConifgArr,$user_id,$entName,$yearsArr
    ){

        if($financeConifgArr['annually_years']<0){
            $config = [
                'IsAnnually' => false,
                'AnnuallyPrice' => false,
                'HasChargedBefore' => false,
            ];

            return $config;
        }

        //包年年度
        $annually_years_arr =  json_decode($financeConifgArr['annually_years'],true);
        //并不是包年年度
        if(
            !in_array(
                $year,
                $annually_years_arr
            )
        ){
            $config = [
                'IsAnnually' => false,
                'AnnuallyPrice' => false,
                'HasChargedBefore' => false,
            ];

            return $config;
        }

        // 单年价格是否按照包年年度计算
        $single_year_charge_as_annual =  $financeConifgArr['single_year_charge_as_annual'];

        // 包年内全部数据
        $yearStr = '("'.implode('","',$annually_years_arr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND 
                ";
        $allDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 包年全部数据中有效的数据
        $yearStr = '("'.implode('","',$annually_years_arr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND
                        `status`  =  ".self::$statusConfirmedYes."
                ";
        $validDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 用户勾选年内全部数据
        $yearStr = '("'.implode('","',$yearsArr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND 
                ";
        $allSeelectedDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 用户勾选的全部数据中有效的数据
        $yearStr = '("'.implode('","',$yearsArr).'")';
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year` in $yearStr  AND
                        user_id = $user_id  AND
                        entName = '$entName' AND
                        `status`  =  ".self::$statusConfirmedYes."
                ";
        $validSelectedDatalist = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        // 单年价格是按照包年年度计算 全部按照单年算
        if($single_year_charge_as_annual){

            $config = [
                // 是否按照包年算
                'IsAnnually' => true,
                //包年计费价格
                'AnnuallyPrice' => $financeConifgArr['annually_price'],
                //全部数据里的有效数据
                'ValidDataIds' => empty($validDatalist) ? false : array_column($validDatalist,'id'),
                //全部数据
                'allDataIds' => empty($allDatalist) ? false : array_column($allDatalist,'id'),
                //用户选择的全部数据
                'allSelectedDataIds' => empty($allSeelectedDatalist) ? false : array_column($allSeelectedDatalist,'id'),
                //用户选择的全部数据里的有效数据
                'validSelectedDatalist' => empty($validSelectedDatalist) ? false : array_column($validSelectedDatalist,'id'),
            ];

            return $config;
        }
        // 单年价格不是按照包年年度计算
        else{

            sort($yearsArr);
            sort($annually_years_arr);
            $yearsArr_str = join(',',$yearsArr);
            $annually_years_arr_str = join(',',$annually_years_arr);
            //用户是否选择了配置的包年年份
            $userHasSelectAllYears = ($yearsArr_str==$annually_years_arr_str)? true:false;


            //用户选择的缺不缺数据
            $allSelectYearsHasDatas  =  false;
            if(
                count($validSelectedDatalist)>0 &&
                count($validSelectedDatalist) == count($allSeelectedDatalist)
            ){
                $allSelectYearsHasDatas  =  true;
            }


            //选择的正好是配置的包年年度
            //且包年年度全部有有效数据
            if(
                $allSelectYearsHasDatas &&
                $userHasSelectAllYears
            ){
                $config = [
                    // 是否按照包年算
                    'IsAnnually' => true,
                    //包年计费价格
                    'AnnuallyPrice' => $financeConifgArr['annually_price'],
                    //全部数据里的有效数据
                    'ValidDataIds' => empty($validDatalist) ? false : array_column($validDatalist,'id'),
                    //全部数据
                    'allDataIds' => empty($allDatalist) ? false : array_column($allDatalist,'id'),
                    //用户选择的全部数据
                    'allSelectedDataIds' => empty($allSeelectedDatalist) ? false : array_column($allSeelectedDatalist,'id'),
                    //用户选择的全部数据里的有效数据
                    'validSelectedDatalist' => empty($validSelectedDatalist) ? false : array_column($validSelectedDatalist,'id'),
                ];

                return $config;
            }
            //没选全部的包年年度 或者有的没数据 按照单年度计费
            else{
                $normal_years_price_arr = json_decode($financeConifgArr['normal_years_price_json'],true);
                $ChargeByYearPrice = 0 ;
                foreach ($normal_years_price_arr as $item){
                    if($item['year'] == $year){
                        $ChargeByYearPrice = $item['price'];
                        break;
                    }
                }
                $config = [
                    // 是否按照包年算
                    'IsAnnually' => false,
                    'ChargeByYear' => true,
                    'ChargeByYearPrice' => $ChargeByYearPrice,
                    //包年计费价格
                    'AnnuallyPrice' => $financeConifgArr['annually_price'],
                    //全部数据里的有效数据
                    'ValidDataIds' => empty($validDatalist) ? false : array_column($validDatalist,'id'),
                    //全部数据
                    'allDataIds' => empty($allDatalist) ? false : array_column($allDatalist,'id'),
                    //用户选择的全部数据
                    'allSelectedDataIds' => empty($allSeelectedDatalist) ? false : array_column($allSeelectedDatalist,'id'),
                    //用户选择的全部数据里的有效数据
                    'validSelectedDatalist' => empty($validSelectedDatalist) ? false : array_column($validSelectedDatalist,'id'),
                ];

                return $config;
            }
        }
    }

    // normal_years_price_json : {"2018":"100","2020":"300"}
    public static function getChagrgeDetailsByYear(
        $year,$financeConifgArr,$user_id,$entName
    ){ 
        $normal_years_price_arr = json_decode($financeConifgArr['normal_years_price_json'],true);
        if(empty($normal_years_price_arr)){
            return [
                'IsChargeByYear' => false,
                'YearPrice' => false,
                'HasChargedBefore' => false,
            ];
        } 

        //不是包年年度
        if(
            !in_array(
               $year,
               array_keys($normal_years_price_arr)
            )
       ){
          return [
                'IsChargeByYear' => false,
                'YearPrice' => false,
                'HasChargedBefore' => false,
          ];
       }

        //是否之前扣过钱 
//        $sql = " select id from  `admin_user_finance_data`
//                    WHERE
//                        `year`  = $year  AND
//                        user_id = $user_id  AND
//                        entName = '$entName' AND
//                        price > 0
//                    limit 1 ";
//        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
//        CommonService::getInstance()->log4PHP(
//            'getChagrgeDetailsByYear    '.$year. '  $sql '.$sql
//        );

        // 全部数据
        $sql = " select id from  `admin_user_finance_data`
                    WHERE
                        `year`  = $year  AND
                        user_id = $user_id  AND
                        entName = '$entName'  
                    limit 1 ";
        $list = sqlRaw($sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        CommonService::getInstance()->log4PHP(
            'getChagrgeDetailsByYear    '.$year. '  $sql '.$sql
        );
        return [
            'IsChargeByYear' => true,
            'YearPrice' => $normal_years_price_arr[$year],
            'allDataIds' => empty($list) ? false : true,
        ]; 
    }

    static function getChargePrice($adminUserFinanceDataArr){
        return  $adminUserFinanceDataArr['price'];
    }

    public static function findByCondition($whereArr,$limit){
        $res =  AdminUserFinanceData::create()
            ->where($whereArr)
            ->limit($limit)
            ->all();  
        return $res;
    }

    public static function findByConditionV2($whereArr,$page,$pageSize){

        $model = AdminUserFinanceData::create()
            ->where($whereArr)
            ->page($page,$pageSize)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    __CLASS__.__FUNCTION__.__LINE__,
                    'lastQuery' => $model->lastQuery()
                ]
            )
        );
        $total = $model->lastQueryResult()->getTotalCount();

        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findByConditionV3($whereArr,$page,$pageSize){
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    __CLASS__.__FUNCTION__.__LINE__,
                    'findByConditionV3' => $whereArr
                ]
            )
        );

        $model = AdminUserFinanceData::create();
        foreach ($whereArr as $whereItem){
            $model->where($whereItem['field'], $whereItem['value'], $whereItem['operate']);
        }
        $model->page($page,$pageSize)
            ->order('id', 'DESC')
            ->withTotalCount();

        $res = $model->all();

        $total = $model->lastQueryResult()->getTotalCount();

        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    __CLASS__.__FUNCTION__.__LINE__,
                    '$res' => $res,
                    '$total' => $total
                ]
            )
        );
        return [
            'data' => $res,
            'total' =>$total,
        ];
    }

    public static function findById($id){
        $res =  AdminUserFinanceData::create()
            ->where('id',$id)            
            ->get();  
        return $res;
    }

    public static function checkIfNoNeed($id){
        $res = self::findById($id);
        if(
            $res->getAttr('status') == self::$statusConfirmedNo
        ){

            return $res->getAttr('status');
        }
       return  false;
    }

    public static function checkIfCheckedBefore($id){
        $res = self::findById($id);
        if(
            in_array($res->getAttr('status'),[self::$statusConfirmedNo,self::$statusConfirmedYes])
        ){

            return $res->getAttr('status');
        }
        return  false;
    }

    public static function checkDataIsValid($id){
        $res =  self::findById($id);
        $res2 =  ($res->getAttr('status') == self::$statusConfirmedYes)? true:false;

        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'checkDataIsValid ',
                    '$id '=>$id,
                    '$res2'=>$res2
                ]
            )
        );
        return $res2;
    }

    public static function checkDataNeedConfirm($id){
        $res =  self::findById($id);
        $res2 =  ($res->getAttr('status') == self::$statusNeedsConfirm)? true:false;

        return $res2;
    }

    public static function findByUserAndEntAndYear($userId,$entName,$year){
        $res =  AdminUserFinanceData::create()
            ->where([
                'user_id' => $userId,  
                'entName' => $entName,  
                'year' => $year,   
            ])
            ->get();  
        return $res;
    }

    static  function  addNewRecordV2($infoArr){
        $AdminUserFinanceDataModel =  AdminUserFinanceData::findByUserAndEntAndYear(
            $infoArr['user_id'],$infoArr['entName'],$infoArr['year']
        );
        if($AdminUserFinanceDataModel){
            $AdminUserFinanceDataId = $AdminUserFinanceDataModel->getAttr('id') ;
          return  $AdminUserFinanceDataId;
        }

        $AdminUserFinanceDataId = AdminUserFinanceData::addRecord(
            $infoArr
        );
        if($AdminUserFinanceDataId <=0 ){
            return  false;
        }

        return  $AdminUserFinanceDataId;
    }

    public static function updatePrice($id,$price,$priceType){
        $info = AdminUserFinanceData::create()
                    ->where('id',$id)
                    ->get(); 
        
        return $info->update([
            'id' => $id,
            'price' => $price,  
            'price_type' => $priceType,  
        ]);
    }

    public static function updateNewFinanceDataId($id,$financeDataId){
        $info = AdminUserFinanceData::findById($id);
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateStatus failed  $id 不存在'.$id
            );
        }
        $financeData = NewFinanceData::findById($financeDataId);
        $financeData = $financeData->toArray();
        $tmpData =[
            'finance_data_id' => $financeDataId,
        ];
        if($info->getAttr('last_pull_api_date')<1){
            $tmpData['last_pull_api_date'] = $financeData['updated_at']?:$financeData['created_at'];
        }
        return $info->update($tmpData);
    }

    public static function updateStatus($id,$status){
        CommonService::getInstance()->log4PHP(
           json_encode([
               'updateStatus  ',$id,$status
           ])
        );
        $info = AdminUserFinanceData::create()
                    ->where('id',$id)
                    ->get(); 
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateStatus failed  $id 不存在'.$id
            );
        }

        return $info->update([
            'id' => $id,
            'status' => $status 
        ]);
    }

    public static function updateNeedsConfirm($id,$needs_confirm){
        $info = self::findById($id);
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateStatus failed  $id 不存在'.$id
            );
        }
        return $info->update([
            'id' => $id,
            'needs_confirm' => $needs_confirm
        ]);
    }

    public static function updateLastPullDate($id,$date){
        $info = AdminUserFinanceData::findById($id);
        CommonService::getInstance()->log4PHP(
            [
                'updateLastPullDate ',
                $id,$date
            ]
        );
        if(!$info ){
            return CommonService::getInstance()->log4PHP(
                'updateLastPullDate failed  $id 不存在'.$id
            );
        }
        return $info->update([
            'id' => $id,
            'last_pull_api_date' => $date
        ]);
    }

    public static function updateLastChargeDate($id,$date){
        CommonService::getInstance()->log4PHP(
           json_encode([
               'user finance data updateLastChargeDate' =>'start',
               '$id' =>$id,
               '$date' =>$date,
           ])
        );
        $info = AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();
        if(!$info ){
            return  CommonService::getInstance()->log4PHP(
                json_encode([
                    'user finance data updateLastChargeDate' =>'faile 1 ',
                    '$id' =>$id,
                    '$date' =>$date,
                ])
            );
        }
        return $info->update([
            'id' => $id,
            'last_charge_date' => $date
        ]);
    }

    public static function updateCacheEndDate($id,$date,$cacheHours){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'user finance data updateCacheEndDate' =>'start  ',
                '$id' =>$id,
                '$date' =>$date,
                '$cacheHours' =>$cacheHours,
            ])
        );

        $info = AdminUserFinanceData::create()
            ->where('id',$id)
            ->get();
        if(!$info ){
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'user finance data updateCacheEndDate' =>'faile 1  ',
                    '$id' =>$id,
                    '$date' =>$date,
                    '$cacheHours' =>$cacheHours,
                ])
            );
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                'user finance data updateCacheEndDate' =>' cache_end_date ',
                '$date' =>$date,
                '$cacheHours' =>$cacheHours,
                'cache_end_date' => date(
                    'Y-m-d H:i',strtotime('+'.$cacheHours.' hours',strtotime($date))
                )
            ])
        );
        return $info->update([
            'id' => $id,
            'cache_end_date' => date(
                'Y-m-d H:i',strtotime('+'.$cacheHours.' hours',strtotime($date))
            )
        ]);
    }

    function  setCostTimes(){

    }

    static function  checkIfAllYearsDataIsValid($user_id,$entName,$years){
        foreach ($years as $year){
            if(
                self::findBySql(
                    " WHERE     user_id = $user_id AND  
                                    entName =  $entName AND year = $year  AND 
                                    status = ".self::$statusinit."
                            "
                )
            ){
               continue;
            };
            $tmpSql =" WHERE     user_id = $user_id AND  
                                    entName =  $entName AND year = $year  AND 
                                    status = ".self::$statusConfirmedYes."
                    ";
            if(
                !self::findBySql($tmpSql)
            ){
                return  CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'checkIfAllYearsDataIsValid. return false ',
                        '$tmpSql' => $tmpSql,
                        '$user_id'=>$user_id,
                        '$entName' =>$entName,
                        '$years'=>$years,
                    ])
                );
            };
        }
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                'checkIfAllYearsDataIsValid. return true ',
                '$user_id'=>$user_id,
                '$entName' =>$entName,
                '$years'=>$years,
            ])
        );
        return  true;
    }
    public static function findBySql($where){
        CommonService::getInstance()->log4PHP(
            json_encode([
                'User Finance Data findBySql',
                '$where' => $where
            ])
        );
        $Sql = " select *  
                            from  
                        `admin_user_finance_data` 
                            $where
      " ;
        $data = sqlRaw($Sql, CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        return $data;
    }
}
