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



class RunDealFinanceCompanyDataNewV3 extends AbstractCronTask
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

    function run(int $taskId, int $workerIndex): bool
    {
        self::parseCompanyDataToDb();
        self::calcluteFinancePrice();
        return true;
    }

    static function  calcluteFinancePrice(){
        //已解析完的 尚未计算价格的
        $where = " WHERE `status` = ".AdminUserFinanceUploadRecordV3::$stateParsed. "   AND touch_time IS Null ";
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

    static function  parseCompanyDataToDb(){
        // 待解析的客户名单
        $uploadRecords = AdminUserFinanceUploadRecord::findBySql(
            " WHERE    `status` = ".AdminUserFinanceUploadRecordV3::$stateInit. "    AND touch_time  IS NULL     ORDER By priority ASC   "
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
