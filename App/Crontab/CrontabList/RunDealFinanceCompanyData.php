<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\AdminV2\AdminUserFinanceData;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadDataRecord;
use App\HttpController\Models\AdminV2\AdminUserFinanceUploadeRecord;
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
        // 将客户名单解析到db
        self::parseDataToDb(1);
        //计算价格
        self::calculatePrice(5);
        //拉取finance数据
        self::pullFinanceData(5); 
        //设置是否需要确认

        return true ;   
    }

    static function pullFinanceData($limit)
    {    
        
        //取财务数据 
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateCalCulatedPrice
            ],
            0,
            $limit
        );  
        foreach($initDatas as $dataItem){
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
                    json_decode($dataItem['finance_config'],true),
                    $$UploadDataRecord['id']
                ); 
                if(!$res){
                    CommonService::getInstance()->log4PHP(
                        'pullFinanceData err2  pull data  error '.$dataItem['id']
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

    //计算单价
    static function calculatePrice($limit)
    {
        //解析完，尚未计算单价的
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateParsed
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
                    AdminUserFinanceUploadRecord::$stateInit,
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                )
            ){
                $changeRes = AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                );
                if(!$changeRes){
                    CommonService::getInstance()->log4PHP(
                        'calculatePrice err1  change status error '.$dataItem['id']
                    );
                    continue;
                }
            } 
            
            // 找到数据记录里还没计算价格的
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
                $calculateRes = AdminUserFinanceData::calculatePrice(
                    $UploadDataRecord['user_finance_data_id'],
                    json_decode($dataItem['finance_config'],true)
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

    //将上传的客户名单解析到db
    static function  parseDataToDb($limit)
    {
        // 用户上传的客户名单信息
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateInit
            ],
            0,
            $limit
        ); 


        foreach($initDatas as $uploadFinanceData){
            // 找到上传的文件
            $dirPat =  dirname($uploadFinanceData['file_path']).DIRECTORY_SEPARATOR;
            self::setworkPath( $dirPat );

            //按行读取数据
            $excelDatas = self::getYieldData($uploadFinanceData['file_name']);
            foreach ($excelDatas as $dataItem) {
                // 按年度解析为数据
                $yearsArr = json_decode($uploadFinanceData['years'],true);

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
                        $AdminUserFinanceDataId = AdminUserFinanceData::addRecord(
                            [
                               'user_id' => $uploadFinanceData['user_id'] , 
                               'entName' => $dataItem[0] ,  
                               'year' => $yearItem ,
                               'finance_data_id' => 0,
                               'price' => 0,
                               'price_type' => 0,
                               'cache_end_date' => 0,
                               'status' => 0,
                            ]
                        );
                    }
                    if($AdminUserFinanceDataId <=0 ){
                        CommonService::getInstance()->log4PHP(
                            'parseDataToDb   err 1 没有 $AdminUserFinanceDataId  '
                        );
                        continue;
                    }

                    // 上传记录表和用户财务基本信息表的关联  生成 AdminUserFinanceData和 AdminUserFinanceUploadDataRecord的关系
                    if(
                        !AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdAndFinanceId(
                            $uploadFinanceData['user_id'],
                            $uploadFinanceData['id'] ,
                            $AdminUserFinanceDataId
                        )
                    ){
                        $AdminUserFinanceUploadDataRecordId = AdminUserFinanceUploadDataRecord::addUploadRecord(
                            [
                                'user_id' => $uploadFinanceData['user_id'] , 
                                'record_id' => $uploadFinanceData['id'] ,    
                                'user_finance_data_id' => $AdminUserFinanceDataId,    
                                'status' => 0,    
                            ]
                        );
                        if($AdminUserFinanceUploadDataRecordId <= 0){
                            CommonService::getInstance()->log4PHP(
                                'parseDataToDb   err 2 没有 $AdminUserFinanceUploadDataRecordId  '
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
