<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;


class RunDealFinanceCompanyData extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/Static/Temp/';
    public static $workPath;
    public $backPath;
    public $all_right_ent_txt_file_name;
    public $have_null_ent_txt_file_name;
    public $data_desc_txt_file_name;

    static function strtr_func($str): string
    {
        $str = trim($str);

        if (empty($str)) {
            return '';
        }

        $arr = [
            '０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4', '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E', 'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O', 'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y', 'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i', 'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's', 'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '(', '〕' => ')', '【' => '[', '】' => ']', '〖' => '[', '〗' => ']',
            '｛' => '{', '｝' => '}', '《' => '<', '》' => '>', '％' => '%', '＋' => '+', '—' => '-', '－' => '-',
            '～' => '~', '：' => ':', '。' => '.', '，' => ',', '、' => ',', '；' => ';', '？' => '?', '！' => '!', '…' => '-',
            '‖' => '|', '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"', '　' => ' ', '×' => '*', '￣' => '~', '．' => '.', '＊' => '*',
            '＆' => '&', '＜' => '<', '＞' => '>', '＄' => '$', '＠' => '@', '＾' => '^', '＿' => '_', '＂' => '"', '￥' => '$', '＝' => '=',
            '＼' => '\\', '／' => '/', '“' => '"', PHP_EOL => ''
        ];

        return str_replace([',', ' '], '', strtr($str, $arr));
    }

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
        $this->createDir(); 
    }

    static function getRule(): string
    {
        return '*/1 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function createDir(): bool
    {
       
        $this->workPath = $this->filePath ;

        return true;
    }  
    
    static function setworkPath($filePath): bool
    {
       
        self::$workPath = $filePath ;

        return true;
    }  

    static function getYieldData($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => self::$workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $value0 = self::strtr_func($one[0]);  
            $value1 = self::strtr_func($one[1]);  
            $value2 = self::strtr_func($one[2]);  
            $value3 = self::strtr_func($one[3]);
            $tmpData = [
                $value0,
                $value1, 
                $value2, 
                $value3, 
            ] ;
            yield $datas[] = $tmpData;
        }
    } 

    //查看AdminUserFinanceUploadDataRecord 表的数据 是否已全部从状态1变更到状态2
    static function checkUploadDataRecordsOldStateIsDone(
        $user_id,
        $id, 
        $state1,
        $state2
    ){
        $initRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $user_id,
            $id,
            $state1
        );
    
        $calculateRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $user_id,
            $id,
            $state2
        );
    
        if(
            empty($initRecords)&&
            $calculateRecords
        ){
            return true;
        }
        return false;
    }

    function run(int $taskId, int $workerIndex): bool
    {
        if(
            !ConfigInfo::checkCrontabIfCanRun("RunDealFinanceCompanyData")
        ){
            return    CommonService::getInstance()->log4PHP(__CLASS__ . ' is running RunDealFinanceCompanyData');

        }
        CommonService::getInstance()->log4PHP(__CLASS__ . ' start running  ');

        ConfigInfo::setIsRunning("RunDealFinanceCompanyData");

        // 将客户名单解析到db
        self::parseDataToDb(1);

        //设置下收费方式
        self::setChargeType(1);

        self::pullFinanceData(1);

        self::downloadFinanceData(1);

        //找到需要导出的
        ConfigInfo::setIsDone("RunDealFinanceCompanyData");

        return true ;   
    }

    function run1(int $taskId, int $workerIndex): bool
    {
        if(
            !ConfigInfo::checkCrontabIfCanRun("RunDealFinanceCompanyData")
        ){
            return    CommonService::getInstance()->log4PHP(__CLASS__ . ' is running RunDealFinanceCompanyData');

        }
        CommonService::getInstance()->log4PHP(__CLASS__ . ' start running  ');

        ConfigInfo::setIsRunning("RunDealFinanceCompanyData");

        // 将客户名单解析到db
        self::parseDataToDb(1);

        return true;
        //计算价格
//        self::calculatePrice(5);
        //拉取finance数据
        self::pullFinanceData(5);

        // 更新对应的计费信息
        self::pullFinanceData(5);


        //计算真实价格
        self::calculateRealPrice(5);

        //生成导出对账记录
        self::calculateRealPrice(5);

        ConfigInfo::setIsDone("RunDealFinanceCompanyData");

        return true ;
    }


    static function AddAccountTransactionFlowData($limit)
    {
        $initDatas =  AdminUserFinanceExportDataQueue::findBySql(
            " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_init. " 
             ORDER BY touch_time ASC  LIMIT $limit 
            "
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceExportDataQueue::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );

            $AdminUserFinanceUploadRecord = AdminUserFinanceUploadRecord::findById($dataItem['upload_record_id']);
            if(!$AdminUserFinanceUploadRecord){
                continue;
            }
            $AdminUserFinanceUploadRecord = $AdminUserFinanceUploadRecord->toArray();
            $finance_config = json_decode($AdminUserFinanceUploadRecord['finance_config'],true);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '$finance_config' =>$finance_config,
                ])
            );
            // 设置导出记录
            if(
                AdminUserFinanceExportRecord::findByQueue($dataItem['id'])
            ){
                continue ;
            }

            $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addExportRecord(
                [
                    'user_id' => $AdminUserFinanceUploadRecord['user_id'],
                    'price' => $AdminUserFinanceUploadRecord['money'],
                    'total_company_nums' => 0,
                    'config_json' => '',
                    'upload_record_id' => $AdminUserFinanceUploadRecord['id'],
                    'reamrk' => '',
                    'status' =>AdminUserFinanceExportRecord::$stateInit,
                    'queue_id' => $dataItem['id'],
                    'batch' => $dataItem['batch'],
                ]
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    '设置导出记录' ,
                    '$AdminUserFinanceExportRecordId' =>$AdminUserFinanceExportRecordId,
                ])
            );


            $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $AdminUserFinanceUploadRecord['user_id'],
                $AdminUserFinanceUploadRecord['id'],
                AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
            );


            // 设置导出详情
            foreach($AdminUserFinanceUploadDataRecord as $dataItem){
                if(
                    AdminUserFinanceExportRecord::findByQueue($dataItem['id'])
                ){
                    continue ;
                }
                AdminUserFinanceExportDataRecord::addExportRecord(
                    [
                        'user_id' => $AdminUserFinanceUploadRecord['user_id'],
                        'export_record_id' => $AdminUserFinanceExportRecordId,
                        'user_finance_data_id' => 0,
                        'price' => $dataItem['real_price'],
                        'detail' => $dataItem['real_price_remark']?:'',
                        'status' => AdminUserFinanceExportRecord::$stateInit,
                    ]
                );

                if($dataItem['real_price'] <=0 ){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'real_price 为0' ,
                        ])
                    );
                    continue;
                }

                $detailRemarks = json_decode($dataItem['real_price_remark'],true);
                if(empty($detailRemarks['allDataIds'])){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            'allDataIds 为空' ,
                        ])
                    );
                    continue;
                };
                foreach ($detailRemarks['allDataIds'] as $idItem){
                    //设置上次计费时间
                    AdminUserFinanceData::updateLastChargeDate(
                        $idItem,
                        date('Y-m-d H:i:s')
                    );
                    //设置缓存过期时间
                    AdminUserFinanceData::updateCacheEndDate(
                        $idItem,
                        date('Y-m-d H:i:s'),
                        $finance_config['cache']
                    );
                }
            }

            //添加计费日志
            FinanceLog::addRecord(
                [
                    'detailId' => $AdminUserFinanceExportRecordId,
                    'detail_table' => 'admin_user_finance_export_record',
                    'price' => $financeData['total_charge'],
//                    'userId' => $this->loginUserinfo['id'],
                    'type' =>  FinanceLog::$chargeTytpeFinance,
                    'title' => '导出财务数据',
                    'detail' => '导出财务数据',
                    'reamrk' => $requestData['reamrk']?:'',
                    'status' => $requestData['status']?:1,
                ]
            );



            if(
                AdminUserChargeConfig::checkIfUserIsValid(
                    $dataItem['user_id']
                )
            ){
                continue;
            }
            // 如果处理完了 设置下状态
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice,
                    AdminUserFinanceUploadRecord::$stateHasGetData
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }

            // 找到需要拉取财务数据的
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            foreach($allUploadDataRecords as $UploadDataRecord){
                // 拉取财务数据
                $res = AdminUserFinanceData::pullFinanceData(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($dataItem['finance_config'],true)
                );
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err2  pull data  error '.$dataItem['id'].' '.$res
                    );
                    continue;
                }
                // 更新拉取时间
                //return true;
                //设置下状态
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err3  update status  error '.$dataItem['id']
                    );
                    continue;
                }
            }
        }

        return true ;
    }

    static function downloadFinanceData($limit)
    {

        //取财务数据
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'pullFinanceData start  ',
                    $limit
                ]

            )
        );
        $where =" WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_init. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas =  AdminUserFinanceExportDataQueue::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'pullFinanceData findBySql  ',
                    $where
                ]

            )
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceExportDataQueue::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );
            $uploadDataRes = AdminUserFinanceUploadRecord::findById($dataItem['upload_record_id'])->toArray();
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkIfUserIsValid  ',
                        $dataItem['id']
                    ]

                )
            );
            if(
                AdminUserChargeConfig::checkIfUserIsValid(
                    $uploadDataRes['user_id']
                )
            ){
                continue;
            }
            // 如果处理完了 设置下状态
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkUploadDataRecordsOldStateIsDone  ',
                        $uploadDataRes['user_id'],
                        $uploadDataRes['id'] ,
                        AdminUserFinanceUploadRecord::$stateParsed,
                        AdminUserFinanceUploadRecord::$stateHasGetData
                    ]

                )
            );
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $uploadDataRes['user_id'],
                    $uploadDataRes['id'] ,
                    AdminUserFinanceUploadRecord::$stateParsed,
                    AdminUserFinanceUploadRecord::$stateHasGetData
                )
            ){

                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadDataRes['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData changeStatus  ',
                            $uploadDataRes['id'],
                            AdminUserFinanceUploadRecord::$stateHasGetData,
                            $changeRes
                        ]

                    )
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$uploadDataRes['id']
                    );
                    continue;
                }
            }

            // 找到需要拉取财务数据的
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData findByUserIdAndRecordId  ',
                        $uploadDataRes['user_id'],
                        $uploadDataRes['id'],
                        AdminUserFinanceUploadRecord::$stateParsed
                    ]

                )
            );
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $uploadDataRes['user_id'],
                $uploadDataRes['id'],
                AdminUserFinanceUploadRecord::$stateParsed
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            foreach($allUploadDataRecords as $UploadDataRecord){
                // 拉取财务数据
                $res = AdminUserFinanceData::pullFinanceData(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($uploadDataRes['finance_config'],true)
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   ',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($uploadDataRes['finance_config'],true),
                            $res
                        ]
                    )
                );
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err2  pull data  error '.$uploadDataRes['id'].' '.$res
                    );
                    continue;
                }
                // 更新拉取时间
                //return true;
                //设置下状态
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   updateStatusById',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($uploadDataRes['finance_config'],true),
                            $res
                        ]

                    )
                );
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err3  update status  error '.$uploadDataRes['id']
                    );
                    continue;
                }
            }
        }

        return true ;
    }

    static function pullFinanceData($limit)
    {    
        
        //取财务数据
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'pullFinanceData start  ',
                    $limit
                ]

            )
        );
        $where =" WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_init. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas =  AdminUserFinanceExportDataQueue::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'pullFinanceData findBySql  ',
                    $where
                ]

            )
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceExportDataQueue::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );
            $uploadDataRes = AdminUserFinanceUploadRecord::findById($dataItem['upload_record_id'])->toArray();
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkIfUserIsValid  ',
                        $dataItem['id']
                    ]

                )
            );
            if(
                AdminUserChargeConfig::checkIfUserIsValid(
                    $uploadDataRes['user_id']
                )
            ){
                continue;
            }
            // 如果处理完了 设置下状态
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkUploadDataRecordsOldStateIsDone  ',
                        $uploadDataRes['user_id'],
                        $uploadDataRes['id'] ,
                        AdminUserFinanceUploadRecord::$stateParsed,
                        AdminUserFinanceUploadRecord::$stateHasGetData
                    ]

                )
            );
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $uploadDataRes['user_id'],
                    $uploadDataRes['id'] ,
                    AdminUserFinanceUploadRecord::$stateParsed,
                    AdminUserFinanceUploadRecord::$stateHasGetData
                )
            ){

                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadDataRes['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData changeStatus  ',
                            $uploadDataRes['id'],
                            AdminUserFinanceUploadRecord::$stateHasGetData,
                            $changeRes
                        ]

                    )
                );
//                AdminUserChargeConfig::
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$uploadDataRes['id']
                    );
                    continue;
                }
            }

            // 找到需要拉取财务数据的
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData findByUserIdAndRecordId  ',
                        $uploadDataRes['user_id'],
                        $uploadDataRes['id'],
                        AdminUserFinanceUploadRecord::$stateParsed
                    ]

                )
            );
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $uploadDataRes['user_id'],
                $uploadDataRes['id'],
                AdminUserFinanceUploadRecord::$stateParsed
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            foreach($allUploadDataRecords as $UploadDataRecord){ 
                // 拉取财务数据
                $res = AdminUserFinanceData::pullFinanceData(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($uploadDataRes['finance_config'],true)
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   ',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($uploadDataRes['finance_config'],true),
                            $res
                        ]
                    )
                );
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err2  pull data  error '.$uploadDataRes['id'].' '.$res
                    );
                    continue;
                }
                // 更新拉取时间
                //return true;
                //设置下状态
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   updateStatusById',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($uploadDataRes['finance_config'],true),
                            $res
                        ]

                    )
                );
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err3  update status  error '.$uploadDataRes['id']
                    );
                    continue;
                }
            }
        } 

        return true ;   
    }

    static function uploadPriceInfo($limit)
    {

        //取财务数据
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'uploadPriceInfo start  ',
                    $limit
                ]

            )
        );
        $where =" WHERE `status` = ".AdminUserFinanceUploadRecord::$stateHasGetData. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas =  AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'uploadPriceInfo findBySql  ',
                    $where
                ]

            )
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceUploadRecord::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkIfUserIsValid  ',
                        $dataItem['user_id']
                    ]

                )
            );
            if(
                AdminUserChargeConfig::checkIfUserIsValid(
                    $dataItem['user_id']
                )
            ){
                continue;
            }
            // 如果处理完了 设置下状态
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData checkUploadDataRecordsOldStateIsDone  ',
                        $dataItem['user_id'],
                        $dataItem['id'] ,
                        AdminUserFinanceUploadRecord::$stateParsed,
                        AdminUserFinanceUploadRecord::$stateHasGetData
                    ]

                )
            );
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateParsed,
                    AdminUserFinanceUploadRecord::$stateHasGetData
                )
            ){

                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData changeStatus  ',
                            $dataItem['id'],
                            AdminUserFinanceUploadRecord::$stateHasGetData,
                            $changeRes
                        ]

                    )
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }

            // 找到需要拉取财务数据的
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'pullFinanceData findByUserIdAndRecordId  ',
                        $dataItem['user_id'],
                        $dataItem['id'],
                        AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                    ]

                )
            );
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            foreach($allUploadDataRecords as $UploadDataRecord){
                // 拉取财务数据
                $res = AdminUserFinanceData::pullFinanceData(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($dataItem['finance_config'],true)
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   ',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($dataItem['finance_config'],true),
                            $res
                        ]
                    )
                );
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err2  pull data  error '.$dataItem['id'].' '.$res
                    );
                    continue;
                }
                // 更新拉取时间
                //return true;
                //设置下状态
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'pullFinanceData   updateStatusById',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($dataItem['finance_config'],true),
                            $res
                        ]

                    )
                );
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err3  update status  error '.$dataItem['id']
                    );
                    continue;
                }
            }
        }

        return true ;
    }


    //计算单价
    static function calculatePrice($limit)
    {
        //解析完，尚未计算单价的
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateParsed. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas =  AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                'calculatePrice start ',
                '$where' => $where
            ])
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceUploadRecord::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );

            // 如果全计算完了 变更下状态
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'calculatePrice checkUploadDataRecordsOldStateIsDone',
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateInit,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                ])
            );
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateInit,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                );
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'calculatePrice changeStatus',
                        $dataItem['id'],
                        AdminUserFinanceUploadRecord::$stateCalCulatedPrice,
                        $changeRes
                    ])
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            } 
            
            // 找到数据记录里还没计算价格的
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'calculatePrice findByUserIdAndRecordId ',
                    $dataItem['user_id'],
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateInit
                ])
            );
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateInit
            );
            if(empty($allUploadDataRecords)){
                continue;
            }
 
            foreach($allUploadDataRecords as $UploadDataRecord){ 
                // 计算单价
                $finance_config = json_decode($dataItem['finance_config'],true);
                $calculateRes = AdminUserFinanceData::calculatePrice(
                    $UploadDataRecord['user_finance_data_id'],
                    $finance_config
                );
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'calculatePrice calculatePrice ',
                        $UploadDataRecord['user_finance_data_id'],
                        $finance_config,
                        $calculateRes
                    ])
                );
                if(!$calculateRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err2  calculate prices error '.$UploadDataRecord['user_finance_data_id']
                    );
                    continue;
                }
                // 计算完价格 变更下状态
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        'calculatePrice updateStatusById ',
                        $UploadDataRecord['id'],
                        AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                    ])
                );
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err2  update status error '.$UploadDataRecord['id']
                    );
                    continue;
                }
            }
        }
        return true ;   
    }

    //检测余额
    static function checkBalancePrice($limit)
    {
        //已经计算完了价格的
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            ],
            0,
            $limit
        );

        foreach($initDatas as $dataItem){
            // 如果全计算完了 变更下状态
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice,
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceOK
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceOK
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice,
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }

            // 找到还没检测余额的
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            $totalNeedsMoney = 0;
            foreach($allUploadDataRecords as $UploadDataRecord){
                $totalNeedsMoney += $UploadDataRecord['real_price'];
            }
            $state = AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo;
            if(
                AdminNewUser::getAccountBalance($dataItem['user_id']) >= $totalNeedsMoney
            ){
                $state = AdminUserFinanceUploadRecord::$stateHasCheckBalanceOK;
            };

            foreach($allUploadDataRecords as $UploadDataRecord){

                // 计算完价格 变更下状态
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    $state
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err2  update status error '.$UploadDataRecord['id']
                    );
                    continue;
                }
            }
        }
        return true ;
    }

    //检测是否账户被关闭 | 临时关闭永久关闭
    static function checkIfAccountIsClosed($limit)
    {

        //已经计算完了价格的
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateHasCheckBalanceOK
            ],
            0,
            $limit
        );

        foreach($initDatas as $dataItem){
            // 如果全计算完了 变更下状态
            if(
                AdminUserChargeConfig::checkIfUserHasRunOutDailyBanance(
                    $dataItem['user_id']
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasDisabledTemp
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice,
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }

            // 找到还没检测余额的
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            $totalNeedsMoney = 0;
            foreach($allUploadDataRecords as $UploadDataRecord){
                $totalNeedsMoney += $UploadDataRecord['real_price'];
            }
            $state = AdminUserFinanceUploadRecord::$stateHasCheckBalanceNo;
            if(
                AdminNewUser::getAccountBalance($dataItem['user_id']) >= $totalNeedsMoney
            ){
                $state = AdminUserFinanceUploadRecord::$stateHasCheckBalanceOK;
            };

            foreach($allUploadDataRecords as $UploadDataRecord){

                // 计算完价格 变更下状态
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    $state
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'checkBalancePrice err2  update status error '.$UploadDataRecord['id']
                    );
                    continue;
                }
            }
        }
        return true ;
    }

    static function calculateRealPrice($limit)
    {
        //尚未计算真实单价的
        $where =" WHERE `status` = ".AdminUserFinanceUploadRecord::$stateHasGetData. " 
             ORDER BY touch_time ASC  LIMIT $limit 
            ";
        $initDatas = AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
           json_encode(
               [
                   'calculateRealPrice',
                   $where
               ]
           )
        );
        foreach($initDatas as $dataItem){
            AdminUserFinanceUploadRecord::setTouchTime(
                $dataItem['id'],date('Y-m-d H:i:s')
            );
            // 如果全计算完了 变更下状态
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'calculateRealPrice checkUploadDataRecordsOldStateIsDone',
                        $dataItem['user_id'],
                        $dataItem['id'] ,
                        AdminUserFinanceUploadRecord::$stateHasGetData,
                        AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
                    ]
                )
            );
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $dataItem['user_id'],
                    $dataItem['id'] ,
                    AdminUserFinanceUploadRecord::$stateHasGetData,
                    AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'calculateRealPrice changeStatus',
                            $dataItem['id'],
                            AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice,
                            $changeRes
                        ]
                    )
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            }

            // 找到数据记录里还没计算价格的
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'calculateRealPrice findByUserIdAndRecordId',
                        $dataItem['user_id'],
                        $dataItem['id'],
                        AdminUserFinanceUploadRecord::$stateHasGetData
                    ]
                )
            );
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadRecord::$stateHasGetData
            );
            if(empty($allUploadDataRecords)){
                continue;
            }

            foreach($allUploadDataRecords as $UploadDataRecord){
                // 计算单价
                $calculateRes = AdminUserFinanceUploadDataRecord::calcluteRealPrice(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($dataItem['finance_config'],true),
                    json_decode($dataItem['years'],true)
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'calculateRealPrice calcluteRealPrice',
                            $UploadDataRecord['user_finance_data_id'],
                            json_decode($dataItem['finance_config'],true)
                        ]
                    )
                );
                if(!$calculateRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err2  calculate prices error '.$UploadDataRecord['user_finance_data_id']
                    );
                    continue;
                }
                // 计算完价格 变更下状态
                $updateRes = AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
                );
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'calculateRealPrice updateStatusById',
                            $UploadDataRecord['id'],
                            AdminUserFinanceUploadRecord::$stateHasCalcluteRealPrice
                        ]
                    )
                );
                if(!$updateRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err2  update status error '.$UploadDataRecord['id']
                    );
                    continue;
                }
            }
        }
        return true ;
    }

    //将上传的客户名单解析到db
    static function  parseDataToDb($limit)
    {
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'RunDealFinanceCompanyData start parseDataToDb'
                ]
            )
        );

        // 用户上传的客户名单信息
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateInit. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas = AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'RunDealFinanceCompanyData parseDataToDb findBySql',
                    $where
                ]
            )
        );
        foreach($initDatas as $uploadFinanceData){
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadFinanceData['id'],date('Y-m-d H:i:s')
            );

            // 如果处理完了 设置下状态
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $uploadFinanceData['user_id'],
                    $uploadFinanceData['id'] ,
                    AdminUserFinanceUploadRecord::$stateInit,
                    AdminUserFinanceUploadRecord::$stateParsed
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadFinanceData['id'],
                    AdminUserFinanceUploadRecord::$stateParsed
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$uploadFinanceData['id']
                    );
                    continue;
                }
            }

            // 找到上传的文件
            $dirPat =  dirname($uploadFinanceData['file_path']).DIRECTORY_SEPARATOR;
            CommonService::getInstance()->log4PHP(
                json_encode(
                    [
                        'RunDealFinanceCompanyData parseDataToDb $dirPat',
                        $dirPat
                    ]
                )
            );
            self::setworkPath( $dirPat );

            //按行读取数据
            $excelDatas = self::getYieldData($uploadFinanceData['file_name']);
            foreach ($excelDatas as $dataItem) {
                // 按年度解析为数据
                $yearsArr = json_decode($uploadFinanceData['years'],true);
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        [
                            'RunDealFinanceCompanyData $parseDataToDb yearsArr',
                            $yearsArr
                        ]
                    )
                );
                foreach($yearsArr as $yearItem){
                    // 插入到AdminUserFinanceData表
                    $AdminUserFinanceDataId = 0 ;

                    // 用户财务数据基本信息表 ;单价，缓存等配置
                    $AdminUserFinanceDataModel =  AdminUserFinanceData::findByUserAndEntAndYear(
                        $uploadFinanceData['user_id'],$dataItem[0],$yearItem
                    );
                    if($AdminUserFinanceDataModel){
                        $AdminUserFinanceDataId = $AdminUserFinanceDataModel->getAttr('id') ;
                    }

                    if(!$AdminUserFinanceDataModel){
                        $tmpData = [
                            'user_id' => $uploadFinanceData['user_id'] ,
                            'entName' => $dataItem[0] ,
                            'year' => $yearItem ,
                            'finance_data_id' => 0,
                            'price' => 0,
                            'price_type' => 0,
                            'cache_end_date' => 0,
                            'status' => 0,
                        ];
                        $AdminUserFinanceDataId = AdminUserFinanceData::addRecord(
                            $tmpData
                        );
                        CommonService::getInstance()->log4PHP(
                            json_encode(
                                [
                                    'RunDealFinanceCompanyData parseDataToDb AdminUserFinanceData  addRecord',
                                    $tmpData,
                                    $AdminUserFinanceDataId
                                ]
                            )
                        );
                    }
                    if($AdminUserFinanceDataId <=0 ){
                        CommonService::getInstance()->log4PHP(
                            json_encode(
                                [
                                 'RunDealFinanceCompanyData parseDataToDb    err 1 没有 $AdminUserFinanceDataId '
                                ]
                            )
                        );
                        continue;
                    }

                    // 上传记录表和用户财务基本信息表的关联  生成 AdminUserFinanceData和 AdminUserFinanceUploadDataRecord的关系
                    CommonService::getInstance()->log4PHP(
                        json_encode(
                            [
                                'RunDealFinanceCompanyData parseDataToDb  AdminUserFinanceUploadDataRecord findByUserIdAndRecordIdAndFinanceId ',
                                'user_id' => $uploadFinanceData['user_id'],
                                '$uploadFinanceData id' => $uploadFinanceData['id'] ,
                                '$AdminUserFinanceDataId' => $AdminUserFinanceDataId
                            ]
                        )
                    );
                    if(
                        !AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdAndFinanceId(
                            $uploadFinanceData['user_id'],
                            $uploadFinanceData['id'] ,
                            $AdminUserFinanceDataId
                        )
                    ){
                        $tmp = [
                            'user_id' => $uploadFinanceData['user_id'] ,
                            'record_id' => $uploadFinanceData['id'] ,
                            'user_finance_data_id' => $AdminUserFinanceDataId,
                            'status' => 0,
                        ];
                        $AdminUserFinanceUploadDataRecordId = AdminUserFinanceUploadDataRecord::addUploadRecord(
                            $tmp
                        );

                        CommonService::getInstance()->log4PHP(
                            json_encode(
                                [
                                    ' RunDealFinanceCompanyData parseDataToDb  AdminUserFinanceUploadDataRecord::addUploadRecord',
                                    $tmp,
                                    $AdminUserFinanceUploadDataRecordId
                                ]
                            )

                        );
                        if($AdminUserFinanceUploadDataRecordId <= 0){
                            CommonService::getInstance()->log4PHP(
                                json_encode(
                                    [
                                        ' RunDealFinanceCompanyData parseDataToDb  $AdminUserFinanceUploadDataRecordId is 0 faile'
                                    ]
                                )

                            );
                            continue;
                        }
                    }    
                } 
            }

            //解析完成-设置状态
            $res = AdminUserFinanceUploadRecord::changeStatus(
                $uploadFinanceData['id'],AdminUserFinanceUploadRecord::$stateParsed
            );
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadRecord::changeStatus',
                    'id' => $uploadFinanceData['id'],
                    'status' => AdminUserFinanceUploadRecord::$stateParsed,
                    'res '=> $res
                ])
            );
            if($res <= 0){
                CommonService::getInstance()->log4PHP(
                    'parseDataToDb   err 3 解析完成-设置状态失败  '
                );
            }
        } 
        return true ;   
    }


    static function  setChargeType($limit)
    {
        //TODO 有需要确认的 需要先去确认字段

        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'RunDealFinanceCompanyData start setChargeType'
                ]
            )
        );

        //
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateParsed. " 
             ORDER BY touch_time ASC  LIMIT $limit 
        ";
        $initDatas = AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                [
                    'RunDealFinanceCompanyData setChargeType findBySql',
                    $where
                ]
            )
        );
        foreach($initDatas as $uploadFinanceData){
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadFinanceData['id'],date('Y-m-d H:i:s')
            );

            // 如果处理完了 设置下状态
            if(
                self::checkUploadDataRecordsOldStateIsDone(
                    $uploadFinanceData['user_id'],
                    $uploadFinanceData['id'] ,
                    AdminUserFinanceUploadRecord::$stateHasGetData,
                    AdminUserFinanceUploadRecord::$stateHasCalclutePriceType
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadFinanceData['id'],
                    AdminUserFinanceUploadRecord::$stateHasGetData
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err1  change status error '.$uploadFinanceData['id']
                    );
                    continue;
                }
            }

            $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $uploadFinanceData['user_id'],
                $uploadFinanceData['id'],
                AdminUserFinanceUploadRecord::$stateParsed
            );

            foreach ($AdminUserFinanceUploadDataRecord as $item){
                $res = AdminUserFinanceUploadDataRecord::updateChargeType(
                    $item['id'],
                    $uploadFinanceData['id']
                );
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        json_encode(
                            [
                                'RunDealFinanceCompanyData setChargeType AdminUserFinanceUploadDataRecord ',
                                $item['id'],
                                $uploadFinanceData['id']
                            ]
                        )
                    );
                    continue;
                }
                //设置状态
                $res = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadFinanceData['id'],AdminUserFinanceUploadRecord::$stateHasCalclutePriceType
                );
            }

            CommonService::getInstance()->log4PHP(
                json_encode([
                    'AdminUserFinanceUploadRecord::changeStatus',
                    'id' => $uploadFinanceData['id'],
                    'status' => AdminUserFinanceUploadRecord::$stateHasCalclutePriceType,
                    'res '=> $res
                ])
            );
            if($res <= 0){
                CommonService::getInstance()->log4PHP(
                    'parseDataToDb   err 3 解析完成-设置状态失败  '
                );
            }
        }
        return true ;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
