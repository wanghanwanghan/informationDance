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
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecordV3;
use App\HttpController\Models\AdminV2\AdminUserSoukeConfig;
use App\HttpController\Models\AdminV2\FinanceLog;
use App\HttpController\Models\AdminV2\NewFinanceData;
use App\HttpController\Models\AdminV2\OperatorLog;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use App\HttpController\Service\Sms\SmsService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use Vtiful\Kernel\Format;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadRecord;
use EasySwoole\RedisPool\Redis;



class RunDealFinanceCompanyDataNewV2 extends AbstractCronTask
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
       
        self::$workPath = $this->filePath ;

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

        //return  true;
        //防止重复跑
        if(
            !ConfigInfo::checkCrontabIfCanRun(__CLASS__)
        ){
            return     CommonService::getInstance()->log4PHP(json_encode(
                [
                    __CLASS__ . ' is already running  ',
                ]
            ));
        }

        //设置为正在执行中
        ConfigInfo::setIsRunning(__CLASS__);

        //将客户名单解析到db
        self::parseCompanyDataToDb(10);

        //计算价格
        self::calcluteFinancePrice(10);

        //找到需要导出的 拉取财务数据
        self::pullFinanceDataV2(10);
//        self::pullFinanceDataV2_v2(10);

        //找到需要导出的 设置为已确认
        self::checkConfirmV2(10);
//        self::checkConfirmV2_V2(10);

        //找到需要导出的 重新拉取财务数据
        self::pullFinanceDataV3(10);
//        self::pullFinanceDataV3_V2(10);

        self::exportFinanceDataV4(10);

        //发生提醒短信
        self::sendSmsWhenBalanceIsNotEnough();

        //设置为已执行完毕
        ConfigInfo::setIsDone(__CLASS__);

        return true ;   
    }

    //拉取财务数据 需要确认的  先拉取 后扣费
    static function  pullFinanceDataV2($limit){
        //计算完价格的
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE 
            `status`  in (  
                        ".AdminUserFinanceUploadRecordV3::$stateCalCulatedPrice. "  ,  
                        ".AdminUserFinanceUploadRecordV3::$stateBalanceNotEnough. " ,
                        ".AdminUserFinanceUploadRecordV3::$stateTooManyPulls. "  
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
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateTooManyPulls);
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                continue;
            }

            //检查余额
            $checkAccountBalanceRes = \App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $uploadRes['user_id'],
                $uploadRes['money']
            );
            if(
                !$checkAccountBalanceRes
            ){
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateBalanceNotEnough);

                //把优先级调低
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setData(
                    $uploadRes['id'],'remrk','检查余额不足'
                );
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                continue;
            }

            //不需要确认的  这阶段不拉取财务数据
            if(
                !AdminUserFinanceConfig::checkIfNeedsConfirm($uploadRes['user_id'])
            ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' =>  "新后台导出财务数据-不需要确认,设置为可以直接导出" ,
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '新后台导出财务数据-不需要确认,设置为可以直接导出',
                    ]
                );
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet);
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                 continue;
            }

            OperatorLog::addRecord(
                [
                    'user_id' => $uploadRes['user_id'],
                    'msg' =>  '新后台导出财务数据-需要确认,要先去拉取财务数据，后扣费',
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-需要确认,要先去拉取财务数据，后扣费',
                ]
            );

            //需要确认的 先去拉取财务数据
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
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateNeedsConfirm);

            }
            //不需要确认
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet);
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
    static function  pullFinanceDataV2_v2($limit){
        //计算完价格的
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE 
            `status`  in (  
                        ".AdminUserFinanceUploadRecordV3::$stateCalCulatedPrice. "  ,  
                        ".AdminUserFinanceUploadRecordV3::$stateBalanceNotEnough. " ,
                        ".AdminUserFinanceUploadRecordV3::$stateTooManyPulls. "  
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
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateTooManyPulls);
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                continue;
            }

            //检查余额
            $checkAccountBalanceRes = \App\HttpController\Models\AdminV2\AdminNewUser::checkAccountBalance(
                $uploadRes['user_id'],
                $uploadRes['money']
            );
            if(
                !$checkAccountBalanceRes
            ){
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateBalanceNotEnough);

                //把优先级调低
                AdminUserFinanceUploadRecord::reducePriority(
                    $uploadRes['id'],1
                );
                AdminUserFinanceUploadRecord::setData(
                    $uploadRes['id'],'remrk','检查余额不足'
                );
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                continue;
            }

            //不需要确认的  这阶段不拉取财务数据
            if(
                !AdminUserFinanceConfig::checkIfNeedsConfirm($uploadRes['user_id'])
            ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' =>  "新后台导出财务数据-不需要确认,设置为可以直接导出" ,
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '新后台导出财务数据-不需要确认,设置为可以直接导出',
                    ]
                );
                AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet);
                AdminUserFinanceUploadRecord::setTouchTime(
                    $uploadRes['id'],NULL
                );
                continue;
            }

            OperatorLog::addRecord(
                [
                    'user_id' => $uploadRes['user_id'],
                    'msg' =>  '新后台导出财务数据-需要确认,要先去拉取财务数据，后扣费',
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-需要确认,要先去拉取财务数据，后扣费',
                ]
            );

            //需要确认的 先去拉取财务数据
            $pullFinanceDataByIdRes = AdminUserFinanceUploadRecord::pullFinanceDataByIdV2(
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
                AdminUserFinanceUploadRecord::checkIfNeedsConfirmV2($uploadRes['id'])
            ){
                $res = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateNeedsConfirm
                );

            }
            //不需要确认
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet
                );
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

    //不需要确认的  先扣费 再拉取
    static function  pullFinanceDataV3($limit){
        $startMemory = memory_get_usage();
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

            //拉取财务数据
            $pullFinanceDataByIdRes = AdminUserFinanceUploadRecord::pullFinanceDataById(
                $uploadRes['id']
            );

            AdminUserFinanceExportDataQueue::updateStatusById($queueData['id'],AdminUserFinanceExportDataQueue::$state_data_all_set);
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );
        }

        return true;
    }

    static function  pullFinanceDataV3_V2($limit){
        $startMemory = memory_get_usage();
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

            //拉取财务数据
            $pullFinanceDataByIdRes = AdminUserFinanceUploadRecord::pullFinanceDataByIdV2(
                $uploadRes['id']
            );

            AdminUserFinanceExportDataQueue::updateStatusById($queueData['id'],AdminUserFinanceExportDataQueue::$state_data_all_set);
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],NULL
            );
        }

        return true;
    }

    static function  checkConfirmV2($limit){
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE `status` = ".AdminUserFinanceUploadRecordV3::$stateNeedsConfirm. " 
             AND touch_time  IS Null  LIMIT $limit 
            "
        );
        foreach($allUploadRes as $uploadRes){

            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],date('Y-m-d H:i:s')
            );

            //尚未确认
            if(
                AdminUserFinanceUploadRecord::checkIfNeedsConfirm($uploadRes['id'])
            ){
                // $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateNeedsConfirm);
            }
            //确认完了
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus($uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet);

                //确认完了 重新计算下价格
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
    static function  checkConfirmV2_V2($limit){
        $allUploadRes =  AdminUserFinanceUploadRecord::findBySql(
            " WHERE `status` = ".AdminUserFinanceUploadRecordV3::$stateNeedsConfirm. " 
                    AND touch_time  IS Null  LIMIT $limit 
            "
        );
        foreach($allUploadRes as $uploadRes){
            AdminUserFinanceUploadRecord::setTouchTime(
                $uploadRes['id'],date('Y-m-d H:i:s')
            );

            //尚未确认
            if(
                AdminUserFinanceUploadRecord::checkIfNeedsConfirmV2($uploadRes['id'])
            ){

            }
            //确认完了
            else{
                $res = AdminUserFinanceUploadRecord::changeStatus(
                    $uploadRes['id'],AdminUserFinanceUploadRecordV3::$stateAllSet
                );

                //确认完了 重新计算下价格
                $calRes = AdminUserFinanceUploadRecord::calMoneyV2(
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

    //TODO  改成按行的 防止内存溢出
    static function  exportFinanceDataV4($limit){
        $startMemory = memory_get_usage();
        $queueDatas =  AdminUserFinanceExportDataQueue::findBySql(
            " WHERE `status` = ".AdminUserFinanceExportDataQueue::$state_data_all_set. " 
                    AND touch_time  IS Null  LIMIT $limit 
            "
        );

        if(empty($queueDatas)){
            return  true;
        }
        foreach($queueDatas as $queueData){
            AdminUserFinanceExportDataQueue::setTouchTime(
                $queueData['id'],date('Y-m-d H:i:s')
            );

            $uploadRes = AdminUserFinanceUploadRecord::findById($queueData['upload_record_id'])->toArray();

            //财务数据
            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV3(
                $uploadRes['user_id'],$uploadRes['id']
            );

            $pathinfo = pathinfo($uploadRes['file_name']);
            $filename = $pathinfo['filename'].'_'.date('YmdHis').'.'.$pathinfo['extension'];

            //===============================
            $config=  [
                'path' => TEMP_FILE_PATH // xlsx文件保存路径
            ];
            $allowedFields = AdminUserFinanceUploadRecord::getAllowedFieldArray($queueData['upload_record_id']);
             $allFields = NewFinanceData::getFieldCname(true);
            $titles =[];
            foreach ($allowedFields as $field){
              $titles[] = $allFields[$field];
             }

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
                ->header($titles)
                ->defaultFormat($alignStyle)
            ;

            foreach ($financeDatas as $dataItem){
                $fileObject ->data([$dataItem['NewFinanceData']]);
            }

            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'generate data done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M'
                ])
            );

            $format = new Format($fileHandle);
            //单元格有\n解析成换行
            $wrapStyle = $format
                ->align(Format::FORMAT_ALIGN_CENTER, Format::FORMAT_ALIGN_VERTICAL_CENTER)
                ->wrap()
                ->toResource();

            $fileObject->output();
            //===============================

            $res = AdminUserFinanceExportDataQueue::setFilePath(
                $queueData['id'],
                '/Static/Temp/',
                $filename
            );
            if(!$res  ){
                return  false;
            }

            // 设置导出记录
            $money = $uploadRes['money'];
            //虽然有价格  但是并没实际收费 （比如本名单已经扣费过）
            if(
                $queueData['real_charge'] == 0
            ){
                $money = 0;
            }

            $tmpData = [
                'user_id' => $uploadRes['user_id'],
                'price' => $money,
                'total_company_nums' => 0,
                'config_json' => $uploadRes['finance_config'],
                'path' => '/Static/Temp/',
                'file_name' => $filename,
                'upload_record_id' => $queueData['upload_record_id'],
                'reamrk' => '',
                'status' =>AdminUserFinanceExportRecord::$stateInit,
                'queue_id' => $queueData['id'],
                'batch' => $queueData['id'],
            ];
            $AdminUserFinanceExportRecordId = AdminUserFinanceExportRecord::addRecordV2(
                $tmpData
            );
            if(!$AdminUserFinanceExportRecordId  ){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRes['user_id'],
                        'msg' =>  "上传记录:".$queueData['upload_record_id']."导出财务数据，文件生成成功，添加导出记录失败 数据：".json_encode($tmpData),
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】新后台导出财务数据-导出财务数据，文件生成成功，添加导出记录失败',
                    ]
                );
                return  false;
            }
            $res =  AdminUserFinanceExportRecord::setFilePath(
                $AdminUserFinanceExportRecordId,
                '/Static/Temp/',
                $filename
            );
            if(!$res  ){
                return  false;
            }

            //设置细的导出记录
            $financeDatas = AdminUserFinanceUploadRecord::getAllFinanceDataByUploadRecordIdV3(
                $uploadRes['user_id'],$uploadRes['id']
            );
            foreach ($financeDatas as $dataItem){
                $priceItem =    intval($dataItem['AdminUserFinanceUploadDataRecord']['real_price']);
                //虽然有价格  但是并没实际收费 （比如本名单已经扣费过）
                if(
                    $queueData['real_charge'] == 0
                ){
                    $priceItem = 0;
                }
                $tmpData2 = [
                    'user_id' => $dataItem['AdminUserFinanceUploadDataRecord']['user_id'],
                    'export_record_id' => $AdminUserFinanceExportRecordId,
                    'upload_data_id' =>   $dataItem['AdminUserFinanceUploadDataRecord']['id'],
                    'price' => $priceItem,
                    'detail' => $dataItem['AdminUserFinanceUploadDataRecord']['price_type_remark']?:'',
                    'batch' => $queueData['id'].'_'.  $dataItem['AdminUserFinanceUploadDataRecord']['id'],
                    'queue_id' => $queueData['id'],
                    'status' => AdminUserFinanceExportRecord::$stateInit,
                ];
                $AdminUserFinanceExportDataRecordId = AdminUserFinanceExportDataRecord::addRecordV2(
                    $tmpData2
                );
                if(!$AdminUserFinanceExportDataRecordId  ){
                    OperatorLog::addRecord(
                        [
                            'user_id' => $uploadRes['user_id'],
                            'msg' =>  "上传记录:".$queueData['upload_record_id']."导出财务数据，文件生成成功，添加详细的导出记录失败 数据：".json_encode($tmpData2),
                            'details' =>json_encode( XinDongService::trace()),
                            'type_cname' => '【失败】新后台导出财务数据-导出财务数据，文件生成成功，添加详细的导出记录失败',
                        ]
                    );
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
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecordV3::$stateParsed. " 
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
                AdminUserFinanceUploadDataRecord::updateChargeInfo(
                    $uploadDataRecord['id'],
                    $uploadRecord['id']
                );
            }

            //实际计算 需要收多少钱
            $res=  AdminUserFinanceUploadRecord::calAndSetMoney(
                $uploadRecord['id']
            );
            if(!$res){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRecord['user_id'],
                        'msg' =>  "上传记录".$uploadRecord['id'].",计算价格失败 ",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】财务定时，计算价格失败',
                    ]
                );
                return false;
            }

            $res = AdminUserFinanceUploadRecord::changeStatus(
                $uploadRecord['id'],AdminUserFinanceUploadRecordV3::$stateCalCulatedPrice
            );
            if(!$res){
                OperatorLog::addRecord(
                    [
                        'user_id' => $uploadRecord['user_id'],
                        'msg' =>  "上传记录".$uploadRecord['id'].",计算价格失败 ",
                        'details' =>json_encode( XinDongService::trace()),
                        'type_cname' => '【失败】财务定时，计算价格成功，改状态失败',
                    ]
                );
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
        foreach ($allConfigs as $Config){
            if(
                $Config['sms_notice_value'] <= 0
            ){
                continue;
            };

            $balance = AdminNewUser::getAccountBalance($Config['user_id']);
            if(
                $balance <= 0
            ){

                continue;
            }

            $userInfo = AdminNewUser::findById($Config['user_id'])->toArray();
            if(
                $userInfo['phone'] <=0
            ){

                continue;
            }

            $chargeConfigs =  AdminUserChargeConfig::findByUser($userInfo['id']);
            if(!$chargeConfigs){
                AdminUserChargeConfig::addRecordV2(
                    [
                        'user_id' => $userInfo['id'],
                        'can_pull_data' => 1,
                        'allowed_daily_nums' => 0,
                        'daily_used_nums' => 0,
                        'allowed_total_nums' => 0,
                        'total_used_nums' => 0,
                        'reamrk' =>'',
                        'status' => 1,
                    ]
                );
                $chargeConfigs =  AdminUserChargeConfig::findByUser($userInfo['id']);
            }
            $chargeConfigs = $chargeConfigs->toArray();

            //余额够了
            if($Config['sms_notice_value'] < $balance ){
                AdminUserChargeConfig::setSmsNoticeDate(
                    $userInfo['id'],
                    ''
                );
                continue;
            }

            //余额不够的

            //之前发过了
            if(
                $chargeConfigs['send_sms_notice_date']  >0
            ){
                continue;
            }

            //需要发短信了
            $res = SmsService::getInstance()->sendByTemplete($userInfo['phone'], 'SMS_244025473',[
                'name' => $userInfo['user_name'],
                'money' =>$Config['sms_notice_value']
            ]);
            AdminUserChargeConfig::setSmsNoticeDate(
                $userInfo['id'],
                date('Y-m-d H:i:s')
            );
            OperatorLog::addRecord(
                [
                    'user_id' => $userInfo['id'],
                    'msg' =>   '用户余额：'.$balance." 配置的余额下限：".$Config['sms_notice_value']." 上次发送时间：".$chargeConfigs['send_sms_notice_date']." ",
                    'details' =>json_encode( XinDongService::trace()),
                    'type_cname' => '新后台导出财务数据-发送短信提醒余额不足',
                ]
            );

        }

        return true;
    }

    //将客户名单 解析到db
    static function  parseCompanyDataToDb($limit){
        // 待解析的客户名单
        $where = " WHERE 
                    `status` = ".AdminUserFinanceUploadRecordV3::$stateInit. " 
                    AND touch_time  IS NULL  
                    ORDER By priority ASC 
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
                if(empty($companyData[0])){
                    continue;
                }
                // 按年度解析为数据
                $yearsArr = json_decode($uploadRecord['years'],true);
                if(empty($yearsArr)){
                    CommonService::getInstance()->log4PHP(
                        json_encode([
                            __CLASS__.__FUNCTION__ ,
                            'error . $yearsArr is emprty . $yearsArr ='=>$yearsArr
                        ])
                    );
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
                        OperatorLog::addRecord(
                            [
                                'user_id' => $uploadRecord['user_id'],
                                'msg' =>  "上传记录".$uploadRecord['id'].",入库admin_user_finance_data表失败 企业：".$companyData[0]." 年度：$yearItem",
                                'details' =>json_encode( XinDongService::trace()),
                                'type_cname' => '【失败】财务定时，解析数据失败',
                            ]
                        );
                        return false;
                    }

                    //如果之前确认过的  需要重新确认
                    if(
                        AdminUserFinanceData::checkIfCheckedBefore($UserFinanceDataId)
                    ){
                        AdminUserFinanceData::updateStatus(
                            $UserFinanceDataId,AdminUserFinanceData::$statusNeedsConfirm
                        );
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
                        OperatorLog::addRecord(
                            [
                                'user_id' => $uploadRecord['user_id'],
                                'msg' =>  "上传记录".$uploadRecord['id'].",入库admin_user_finance_upload_data_record表失败  admin_user_finance_data表id：$UserFinanceDataId",
                                'details' =>json_encode( XinDongService::trace()),
                                'type_cname' => '【失败】财务定时，解析数据失败',
                            ]
                        );
                        return false;
                    }
                }
            }
            AdminUserFinanceUploadRecord::changeStatus(
                $uploadRecord['id'],AdminUserFinanceUploadRecordV3::$stateParsed
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
