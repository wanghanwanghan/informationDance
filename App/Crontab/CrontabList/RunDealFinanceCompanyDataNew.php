<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataQueue;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceExportRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;


class RunDealFinanceCompanyDataNew extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/Static/Temp/';
    public static $workPath;

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
     /*
      * step1:上传客户名单
      * step2:定时将客户名单解析到数据库
      * step3:定时算一下价格（大致价格|有的缺失数据的 需要拉完财务数据重算） 算一下总价
      * step4:点击导出-加入到队列
      * step5:跑api数据
      * step7:先去确认
      * step8:重算价格
      * step9:生成文件，扣费，记录导出详情
      * */
    function run(int $taskId, int $workerIndex): bool
    {
        if(
            !ConfigInfo::checkCrontabIfCanRun("RunDealFinanceCompanyData2")
        ){
            return     CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ . ' is already running  ',
                ]
            ));
        }
        CommonService::getInstance()->log4PHP(json_encode(
            [
                __CLASS__ . ' start running  ',
            ]
        ));

        ConfigInfo::setIsRunning("RunDealFinanceCompanyData2");

        //将客户名单解析到db
        self::parseCompanyDataToDb(1);
        self::calcluteFinancePrice(1);
        //找到需要导出的 拉取财务数据
        self::pullFinanceData(5);
        //找到需要导出的 设置为已确认
        self::checkConfirm(5);
        //重新计算价格
        //找到已确认完的 实际导出
        self::exportFinanceData(5);

        ConfigInfo::setIsDone("RunDealFinanceCompanyData2");

        return true ;   
    }

    static function  pullFinanceData($limit){
        $queueDatas =  AdminUserFinanceExportDataQueue::findBySql(
            " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_init. " 
             AND touch_time  IS Null  LIMIT $limit 
            "
        );

        foreach($queueDatas as $queueData){

            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],date('Y-m-d H:i:s')
            );

            $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();

            $finance_config = AdminUserFinanceUploadRecord::getFinanceConfigArray($queueData['upload_record_id']);

            //拉取财务数据
            $pullFinanceDataByIdRes = AdminUserFinanceUploadRecord::pullFinanceDataById(
                $uploadRes['id']
            );

            //设置是否需要去确认
            AdminUserFinanceExportDataQueue::setFinanceDataState($queueData['id']);
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );
        }

        return true;
    }

    static function  checkConfirm($limit){
        $queueDatas =  AdminUserFinanceExportDataQueue::findBySql(
            " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_needs_confirm. " 
             AND touch_time  IS Null  LIMIT $limit 
            "
        );
        CommonService::getInstance()->log4PHP(
            json_encode([
                'exportFinanceData   '=> 'strat',
                " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_needs_confirm. " 
             AND touch_time  IS Null  LIMIT $limit 
            "

            ])
        );
        foreach($queueDatas as $queueData){

            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],date('Y-m-d H:i:s')
            );

            $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'exportFinanceData   '=> '$uploadRes',
                    $uploadRes
                ])
            );
            $finance_config = AdminUserFinanceUploadRecord::getFinanceConfigArray($queueData['upload_record_id']);
            CommonService::getInstance()->log4PHP(
                json_encode([
                    'exportFinanceData   '=> '$finance_config',
                    $finance_config
                ])
            );

            //设置是否需要去确认
            AdminUserFinanceExportDataQueue::setFinanceDataState($queueData['id']);

            //重新计算下价格
            AdminUserFinanceUploadRecord::calAndSetMoney(
                $queueData['upload_record_id']
            );

            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );
        }

        return true;
    }

    static function  exportFinanceData($limit){
        $queueDatas =  AdminUserFinanceExportDataQueue::findBySql(
            " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_confirmed. " 
             AND touch_time  IS Null  LIMIT $limit 
            "
        );
        foreach($queueDatas as $queueData){
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],date('Y-m-d H:i:s')
            );

            $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();

            $finance_config = AdminUserFinanceUploadRecord::getFinanceConfigArray($queueData['upload_record_id']);


            //财务数据
            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV2(
                $uploadRes['user_id'],$uploadRes['id']
            );

            //生成下载数据
            $xlxsData = NewFinanceData::exportFinanceToXlsx($queueData['upload_record_id'],$financeDatas['export_data']);

            AdminUserFinanceExportDataQueue::setFilePath(
                $queueData['id'],
                $xlxsData['path'],
                $xlxsData['filename']
            );
            //之前是否扣费过
            $chargeBefore = AdminUserFinanceUploadRecord::ifHasChargeBefore($uploadRes['id']);
            // 实际扣费
            $price = 0;
            if(
                $uploadRes['money'] > 0 &&
                !$chargeBefore
            ){
                $price = $uploadRes['money'];
                $res = \App\HttpController\Models\AdminV2\AdminNewUser::charge(
                    $uploadRes['user_id'],
                    $uploadRes['money'],
                    $queueData['id'],
                    [
                        'detailId' => $queueData['id'],
                        'detail_table' => 'admin_user_finance_export_data_queue',
                        'price' => $uploadRes['money'],
                        'userId' => $uploadRes['user_id'],
                        'type' => FinanceLog::$chargeTytpeFinance,
                        'batch' => $queueData['id'],
                        'title' => '',
                        'detail' => '',
                        'reamrk' => '',
                        'status' => 1,
                    ],
                    10
                );
                if(!$res ){
                    continue;
                }
                AdminUserFinanceUploadRecord::updateLastChargeDate($uploadRes['id'],date('Y-m-d H:i:s'));
            }

            // 设置导出记录
            $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addRecordV2(
                [
                    'user_id' => $uploadRes['user_id'],
                    'price' => $price,
                    'total_company_nums' => 0,
                    'config_json' => $uploadRes['finance_config'],
                    'path' => $xlxsData['path'],
                    'file_name' => $xlxsData['filename'],
                    'upload_record_id' => $queueData['upload_record_id'],
                    'reamrk' => '',
                    'status' =>AdminUserFinanceExportRecord::$stateInit,
                    'queue_id' => $queueData['id'],
                    'batch' => $queueData['id'],
                ]
            );
            if(!$AdminUserFinanceExportRecordId){
                continue ;
            }
            AdminUserFinanceExportRecord::setFilePath(
                $AdminUserFinanceExportRecordId,
                $xlxsData['path'],
                $xlxsData['filename']
            );

            foreach($financeDatas['details'] as $financeData){
                $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::
                    findById($financeData['UploadDataRecordId'])->toArray();
               $priceItem =    intval($AdminUserFinanceUploadDataRecord['real_price']);
               if($chargeBefore){
                   $priceItem = 0;
               }
                $AdminUserFinanceExportDataRecordId = AdminUserFinanceExportDataRecord::addRecordV2(
                    [
                        'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                        'export_record_id' => $AdminUserFinanceExportRecordId,
                        'upload_data_id' => $financeData['UploadDataRecordId'],
                        'price' => $priceItem,
                        'detail' => $AdminUserFinanceUploadDataRecord['price_type_remark']?:'',
                        'batch' => $queueData['id'].'_'.$financeData['UploadDataRecordId'],
                        'queue_id' => $queueData['id'],
                        'status' => AdminUserFinanceExportRecord::$stateInit,
                    ]
                );
               // 如果真收费了
                if($priceItem){
                    //设置收费记录
                    $AdminUserFinanceChargeInfoId = AdminUserFinanceChargeInfo::addRecordV2(
                        [
                            'user_id' => $AdminUserFinanceUploadDataRecord['user_id'],
                            'batch' => $AdminUserFinanceUploadDataRecord['id'].'_'.$queueData['id'],
                            'entName' => $financeData['entName'],
                            'start_year' => $AdminUserFinanceUploadDataRecord['charge_year_start'],
                            'end_year' => $AdminUserFinanceUploadDataRecord['charge_year_end'],
                            'year' => $AdminUserFinanceUploadDataRecord['charge_year'],
                            'price' => $priceItem,
                            'price_type' => $AdminUserFinanceUploadDataRecord['price_type'],
                            'status' => AdminUserFinanceChargeInfo::$state_init,
                        ]
                    );
                    //设置上次计费时间
                    AdminUserFinanceData::updateLastChargeDate(
                        $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                        date('Y-m-d H:i:s')
                    );

                    //设置缓存过期时间
                    AdminUserFinanceData::updateCacheEndDate(
                        $AdminUserFinanceUploadDataRecord['user_finance_data_id'],
                        date('Y-m-d H:i:s'),
                        $finance_config['cache']
                    );
                }
            }


            AdminUserFinanceExportDataQueue::updateStatusById(
                $queueData['id'],
                AdminUserFinanceExportDataQueue::$state_succeed
            ); 
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );

        }

        return true;
    }

    //计算价格
    static function  calcluteFinancePrice($limit){
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateParsed. " 
             AND touch_time IS Null  LIMIT $limit 
        ";
        $uploadRecords = AdminUserFinanceUploadRecord::findBySql(
            $where
        );

        foreach($uploadRecords as $uploadRecord) {
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], date('Y-m-d H:i:s')
            );

            $uploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
                $uploadRecord['user_id'],
                $uploadRecord['id']
            );
            foreach ($uploadDataRecords as $uploadDataRecord){
                AdminUserFinanceUploadDataRecord::updateChargeInfo(
                    $uploadDataRecord['id'],
                    $uploadRecord['id']
                );
            }
            $res = AdminUserFinanceUploadRecord::changeStatus(
                $uploadRecord['id'],AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if($res <= 0){
            }

            AdminUserFinanceUploadRecord::calAndSetMoney(
                $uploadRecord['id']
            );

            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], NULL
            );
        }
        return true;
    }
    //将客户名单 解析到db
    static function  parseCompanyDataToDb($limit){
        // 用户上传的客户名单信息
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateInit. " 
             AND touch_time  IS NULL   LIMIT $limit 
        ";
        $uploadRecords = AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        CommonService::getInstance()->log4PHP(
            json_encode(
                ['parseCompanyDataToDb  findBySql ',$where,$uploadRecords]
            )
        );
        foreach($uploadRecords as $uploadRecord) {
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], date('Y-m-d H:i:s')
            );
            // 找到上传的文件路径
            $dirPath =  dirname($uploadRecord['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $dirPath );
            CommonService::getInstance()->log4PHP(
                json_encode(
                    ['parseCompanyDataToDb  setworkPath ','$dirPath'=>$dirPath]
                )
            );
            //按行读取数据
            $companyDatas = self::getYieldData($uploadRecord['file_name']);
            CommonService::getInstance()->log4PHP(
                json_encode(
                    ['parseCompanyDataToDb  getYieldData ','file_name'=>$uploadRecord['file_name']]
                )
            );
            foreach ($companyDatas as $companyData) {
                // 按年度解析为数据
                $yearsArr = json_decode($uploadRecord['years'],true);
                CommonService::getInstance()->log4PHP(
                    json_encode(
                        ['parseCompanyDataToDb  json_decode year ','$yearsArr'=>$yearsArr]
                    )
                );
                foreach($yearsArr as $yearItem){
                    $tmp =[
                        'user_id' => $uploadRecord['user_id'] ,
                        'entName' => $companyData[0] ,
                        'year' => $yearItem ,
                        'finance_data_id' => 0,
                        'price' => 0,
                        'price_type' => 0,
                        'cache_end_date' => 0,
                        'status' => AdminUserFinanceData::$statusinit,
                    ];
                    $UserFinanceDataId =  AdminUserFinanceData::addNewRecordV2(
                        $tmp
                    );
                    if(!$UserFinanceDataId){
                        continue ;
                    }

                    $UserFinanceUploadDataRecordId = AdminUserFinanceUploadDataRecord::addRecordV2(
                        [
                            'user_id' => $uploadRecord['user_id'] ,
                            'record_id' => $uploadRecord['id'] ,
                            'user_finance_data_id' => $UserFinanceDataId,
                            'status' => 0,
                        ]
                    );
                    if($UserFinanceUploadDataRecordId <= 0){
                        continue;
                    }
                }
            }
            $res = AdminUserFinanceUploadRecord::changeStatus(
                $uploadRecord['id'],AdminUserFinanceUploadRecord::$stateParsed
            );

            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], NULL
            );
        }

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
