<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Admin\SaibopengkeAdmin\FinanceChargeLog;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Models\AdminV2\AdminNewUser;
use App\HttpController\Models\AdminV2\AdminUserChargeConfig;
use App\HttpController\Models\AdminV2\AdminUserFinanceChargeInfo;
use App\HttpController\Models\AdminV2\AdminUserFinanceConfig;
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
use App\HttpController\Service\Sms\SmsService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use EasySwoole\RedisPool\Redis;



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
        //防止重复跑
        if(
            !ConfigInfo::checkCrontabIfCanRun("RunDealFinanceCompanyData2")
        ){
            return     CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ . ' is already running  ',
                ]
            ));
        }

        //设置为正在执行中
        ConfigInfo::setIsRunning("RunDealFinanceCompanyData2");

        //将客户名单解析到db
        self::parseCompanyDataToDb(3);

        //计算价格|
        self::calcluteFinancePrice(3);

        //找到需要导出的 拉取财务数据
        self::pullFinanceDataV2(3);

        //找到需要导出的 设置为已确认
        self::checkConfirmV2(3);

        //找到已确认完的 实际导出
        self::exportFinanceDataV3(3);

        //发生提醒短信
        self::sendSmsWhenBalanceIsNotEnough();

        //设置为已执行完毕
        ConfigInfo::setIsDone("RunDealFinanceCompanyData2");

        return true ;   
    }

    //拉取财务数据
    static function  pullFinanceDataV2($limit){
        //计算完价格的
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE 
            `status`  in (  
                        ".AdminUserFinanceUploadRecord::$stateCalCulatedPrice. "  ,  
                        ".AdminUserFinanceUploadRecord::$stateBalanceNotEnough. " ,
                        ".AdminUserFinanceUploadRecord::$stateTooManyPulls. "  
            )
             AND touch_time  IS Null
             ORDER BY priority ASC 
           LIMIT $limit 
            "
        );

        foreach($allUploadRes as $uploadRes){
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],date('Y-m-d H:i:s')
            );

            //每日最大次数限制
            if(
                !AdminUserChargeConfig::checkIfCanRun(
                    $uploadRes['user_id'],
                    count(
                        AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
                            $uploadRes['user_id'],
                            $uploadRes['id'],
                            ['id']
                        )
                    )
                )
            ){
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateTooManyPulls);
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                return  false;
            }

            //检查余额
            if(
                !\App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                    $uploadRes['user_id'],
                    $uploadRes['money']
                )
            ){
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateBalanceNotEnough);

                //把优先级调低
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setData(
                    $uploadRes['id'],'remrk','检查余额不足'
                );
                return  false;
            }

            //拉取财务数据
            $pullFinanceDataByIdRes = AdminUserFinanceUploadRecord::pullFinanceDataById(
                $uploadRes['id']
            );
            if(!$pullFinanceDataByIdRes){
                //把优先级调低
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setData(
                    $uploadRes['id'],'remrk','拉取财务数据失败'
                );
                return  false;
            }

            //要去确认
            if(
                AdminUserFinanceUploadRecord::checkIfNeedsConfirm($uploadRes['id'])
            ){
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateNeedsConfirm);

            }
            //不需要确认
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateConfirmed);
            }

            if(!$res){
                //把优先级调低
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setData(
                    $uploadRes['id'],'remrk','设置确认状态错误'
                );
                return  false;
            }
            ;

            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],NULL
            );
        }

        return true;
    }


    static function  checkConfirmV2($limit){
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateNeedsConfirm. " 
             AND touch_time  IS Null  LIMIT $limit 
            "
        );
        foreach($allUploadRes as $uploadRes){

            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],date('Y-m-d H:i:s')
            );

            //要去确认
            if(
                AdminUserFinanceUploadRecord::checkIfNeedsConfirm($uploadRes['id'])
            ){
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateNeedsConfirm);
            }
            //不需要确认
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecord::$stateConfirmed);

                //重新计算下价格
                $calRes = AdminUserFinanceUploadRecord::calAndSetMoney(
                    $uploadRes['id']
                );

                if(!$calRes  ){
                    //把优先级调低
                    AdminUserFinanceUploadRecord::reducePriority(
                        $uploadRes['id'],1
                    );
                    AdminUserFinanceUploadRecord::setData(
                        $uploadRes['id'],'remrk','重新计算价格错误'
                    );
                    return  false;
                }
            }


            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],NULL
            );
        }

        return true;
    }


    static function  exportFinanceDataV3($limit){
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

            //财务数据
            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV2(
                $uploadRes['user_id'],$uploadRes['id']
            );

            //生成下载数据
            $xlxsData = NewFinanceData::exportFinanceToXlsx($queueData['upload_record_id'],$financeDatas['export_data']);

            $res = AdminUserFinanceExportDataQueue::setFilePath(
                $queueData['id'],
                $xlxsData['path'],
                $xlxsData['filename']
            );
            if(!$res  ){
                return  false;
            }


            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV2(
                $uploadRes['user_id'],$uploadRes['id']
            );

            // 设置导出记录
            $money = $uploadRes['money'];
            //虽然有价格  但是并没实际收费 （比如本名单已经扣费过）
            if(
                $queueData['real_charge'] == 0
            ){
                $money = 0;
            }

            $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addRecordV2(
                [
                    'user_id' => $uploadRes['user_id'],
                    'price' => $money,
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
            if(!$AdminUserFinanceExportRecordId  ){
                return  false;
            }
            $res =  AdminUserFinanceExportRecord::setFilePath(
                $AdminUserFinanceExportRecordId,
                $xlxsData['path'],
                $xlxsData['filename']
            );
            if(!$res  ){
                return  false;
            }

            //设置细的导出记录
            foreach($financeDatas['details'] as $financeData){
                $AdminUserFinanceUploadDataRecord = AdminUserFinanceUploadDataRecord::findById($financeData['UploadDataRecordId'])->toArray();
                $priceItem =    intval($AdminUserFinanceUploadDataRecord['real_price']);
                //虽然有价格  但是并没实际收费 （比如本名单已经扣费过）
                if(
                    $queueData['real_charge'] == 0
                ){
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
                if(!$AdminUserFinanceExportDataRecordId  ){
                    return  false;
                }
            }

            $res = AdminUserFinanceExportDataQueue::updateStatusById(
                $queueData['id'],
                AdminUserFinanceExportDataQueue::$state_succeed
            );
            if(!$res  ){
                return  false;
            }
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );

        }

        return true;
    }

    //计算价格
    static function  calcluteFinancePrice($limit){
        //已解析完的 尚未计算价格的
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecord::$stateParsed. " 
             AND touch_time IS Null  LIMIT $limit 
        ";
        $uploadRecords = AdminUserFinanceUploadRecord::findBySql(
            $where
        );

        foreach($uploadRecords as $uploadRecord) {
            //touch time  标识|表示在处理中
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], date('Y-m-d H:i:s')
            );

            $uploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdV2(
                $uploadRecord['user_id'],
                $uploadRecord['id']
            );
            foreach ($uploadDataRecords as $uploadDataRecord){
                $res = AdminUserFinanceUploadDataRecord::updateChargeInfo(
                    $uploadDataRecord['id'],
                    $uploadRecord['id']
                );
                if(!$res){
                    return false;
                }
            }
            $res = AdminUserFinanceUploadRecord::changeStatus(
                $uploadRecord['id'],AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            );
            if(!$res){
                return false;
            }
            //实际计算 需要收多少钱
            $res=  AdminUserFinanceUploadRecord::calAndSetMoney(
                $uploadRecord['id']
            );
            if(!$res){
                return false;
            }
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], NULL
            );
        }
        return true;
    }

    static function  sendSmsWhenBalanceIsNotEnough(){
        $redis = Redis::defer('redis');
        $redis->select(14);
        $allConfigs  = AdminUserFinanceConfig::findAllByCondition([
            'status' => AdminUserFinanceConfig::$state_ok
        ]);
        CommonService::getInstance()->log4PHP(
            json_encode([
                __CLASS__.__FUNCTION__ ,
                '$allConfigs '=> $allConfigs,
            ])
        );
        foreach ($allConfigs as $Config){
            if(
                $Config['sms_notice_value'] <= 0
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'sms_notice_value < 0 . continue '=>$Config['sms_notice_value']
                    ])
                );
                continue;
            };

            $balance = AdminNewUser::getAccountBalance($Config['user_id']);
            if(
                $balance <= 0
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        '$balance < 0 . continue '=>$balance
                    ])
                );
                continue;
            }

            $userInfo = AdminNewUser::findById($Config['user_id'])->toArray();
            if(
                $userInfo['phone'] <=0
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'phone < 0 . continue '=>$userInfo['phone']
                    ])
                );
                continue;
            }

            if(
                $redis->get($userInfo['phone'].'_sms_notice_value')
            ){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'has send before  . continue '
                    ])
                );
                continue;
            }

            //需要发短信了
            if($Config['sms_notice_value'] <= $balance ){
                $res = SmsService::getInstance()->comm($userInfo['phone'], 'XXX');
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ ,
                        'send sms ',
                        '$res' => $res,
                        'phone' => $userInfo['phone'],

                    ])
                );
                $redis->set($userInfo['phone'].'_sms_notice_value', 1, 60*60*24);
            }
        }

        return true;
    }

    //将客户名单 解析到db
    static function  parseCompanyDataToDb($limit){
        // 待解析的客户名单
        $where = " WHERE 
                    `status` = ".AdminUserFinanceUploadRecord::$stateInit. " 
                    AND touch_time  IS NULL   
                    LIMIT $limit 
        ";
        $uploadRecords = AdminUserFinanceUploadRecord::findBySql(
            $where
        );
        foreach($uploadRecords as $uploadRecord) {
            //touch time：占用符 标识该条记录在执行中  防止重复执行
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRecord['id'], date('Y-m-d H:i:s')
            );

            // 找到上传的文件路径
            $dirPath =  dirname($uploadRecord['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $dirPath );

            //按行读取数据
            $companyDatas = self::getYieldData($uploadRecord['file_name']);

            foreach ($companyDatas as $companyData) {
                // 按年度解析为数据
                $yearsArr = json_decode($uploadRecord['years'],true);
                //$yearsArr = explode(',',$uploadRecord['years']);
                if(empty($yearsArr)){
                    continue;
                }
                foreach($yearsArr as $yearItem){
                    $UserFinanceDataId =  AdminUserFinanceData::addNewRecordV2(
                        [
                            'user_id' => $uploadRecord['user_id'] ,
                            'entName' => $companyData[0] ,
                            'year' => $yearItem ,
                            'finance_data_id' => 0,
                            'price' => 0,
                            'price_type' => 0,
                            'cache_end_date' => 0,
                            'status' => AdminUserFinanceData::$statusinit,
                        ]
                    );
                    if(!$UserFinanceDataId){
                        return false;
                    }

                    $res =AdminUserFinanceUploadDataRecord::addRecordV2(
                        [
                            'user_id' => $uploadRecord['user_id'] ,
                            'record_id' => $uploadRecord['id'] ,
                            'user_finance_data_id' => $UserFinanceDataId,
                            'status' => 0,
                        ]
                    );
                    if(!$res){
                        return false;
                    }
                }
            }
            AdminUserFinanceUploadRecord::changeStatus(
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
