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
    public $workPath;
    public $backPath;
    public $all_right_ent_txt_file_name;
    public $have_null_ent_txt_file_name;
    public $data_desc_txt_file_name;

    function strtr_func($str): string
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
    
    function setworkPath($filePath): bool
    {
       
        $this->workPath = $filePath ;

        return true;
    }  

    // function getYieldData($xlsx_name,$formatFuncName){
    function getYieldData($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
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

            $value0 = $this->strtr_func($one[0]);  
            $value1 = $this->strtr_func($one[1]);  
            $value2 = $this->strtr_func($one[2]);  
            $value3 = $this->strtr_func($one[3]);   
            // $tmpData = (new XinDongService())->matchEntByName($value0,1,4);
            $tmpData = [
                $value0,
                $value1, 
                $value2, 
                $value3, 
            ] ;
            CommonService::getInstance()->log4PHP('matchNamXXXX'.json_encode(
                [
                    'value' => [$value0,$value1],
                    'params' => $value0,
                    'res' => $tmpData
                ]
            )); 
            yield $datas[] = $tmpData;
        }
    }
  
    function matchName($file,$debugLog){
        $startMemory = memory_get_usage(); 

        // 取yield数据 
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $excelDatas = $this->getYieldData($file); 

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用1 '.$memory .' '.$file );

        //写到csv里 
        $fileName = pathinfo($file)['filename'];
        $f = fopen($this->workPath.$fileName.".csv", "w");
        fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($excelDatas as $dataItem) {
            fputcsv($f, $dataItem);
        }

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用2 '.$memory .' '.$file );

        @unlink($this->workPath . $file);

        return true ;  
    }

     function saveAndReturnAdminUserFinanceDataId(){
         
     }

     function uploadRecordeHasAllCalCulated($user_id,$id){
        $initRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $user_id,
            $id,
            AdminUserFinanceUploadDataRecord::$stateInit
        );

        $calculateRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
            $user_id,
            $id,
            AdminUserFinanceUploadDataRecord::$stateInit
        );

         if(
             empty($initRecords)&&
             $calculateRecords
         ){
            return true;
         }
     }

    function run(int $taskId, int $workerIndex): bool
    {   
        // 将客户名单解析到db
        $this->parseDataToDb(1);
        //计算价格
        $this->calculatePrice(5);
            
        return true ;   
    }

    function calculatePrice($limit)
    {    
        //计算单价
        //解析完，尚未计算单价的
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateParsed
            ],
            $limit
        );
       
        foreach($initDatas as $dataItem){ 
            // 如果全计算完了 变更下状态
            if(
                $this->uploadRecordeHasAllCalCulated(
                    $dataItem['user_id'],
                    $dataItem['id'] 
                )
            ){
                AdminUserFinanceUploadRecord::changeStatus(
                    $dataItem['id'],
                    AdminUserFinanceUploadRecord::$stateCalCulatedPrice
                );
            } 
            
            // 找到还没计算价格的
            $allUploadDataRecords =  AdminUserFinanceUploadDataRecord::findByUserIdAndRecordId(
                $dataItem['user_id'],
                $dataItem['id'],
                AdminUserFinanceUploadDataRecord::$stateInit
            );
            if(empty($allUploadDataRecords)){
                continue;
            }
 
            foreach($allUploadDataRecords as $UploadDataRecord){ 
                // 计算单价
                AdminUserFinanceData::calculatePrice(
                    $UploadDataRecord['user_finance_data_id'],
                    $dataItem['finance_config']
                );
                // 计算完价格 变更下状态
                AdminUserFinanceUploadDataRecord::updateStatusById(
                    $UploadDataRecord['id'],
                    AdminUserFinanceUploadDataRecord::$stateHasCalculatePrice
                );
            }
        }
        return true ;   
    }

    //将客户名单解析到db 
    function parseDataToDb($limit)
    {
        // 用户上传的客户名单信息
        $initDatas = AdminUserFinanceUploadRecord::findByCondition(
            [
                'status' => AdminUserFinanceUploadRecord::$stateInit
            ],
            $limit
        );
        foreach($initDatas as $uploadFinanceData){ 
            // 找到上传的文件
            $dirPat =  dirname($uploadFinanceData['file_path']).DIRECTORY_SEPARATOR; 
            $this->setworkPath( $dirPat );
            //按行读取数据
            $excelDatas = $this->getYieldData($uploadFinanceData['file_path']); 
            foreach ($excelDatas as $dataItem) { 
                // 按年度解析为数据
                $yearsArr = explode(',',$uploadFinanceData['years']); 
                foreach($yearsArr as $yearItem){
                    // 插入到AdminUserFinanceData表
                    $AdminUserFinanceDataId = 0 ;
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
                    
                    // 生成 AdminUserFinanceData和 AdminUserFinanceUploadRecord的关系
                    if(
                        !AdminUserFinanceUploadDataRecord::findByUserIdAndRecordIdAndFinanceId(
                            $uploadFinanceData['user_id'],$uploadFinanceData['id'] ,$AdminUserFinanceDataId                     
                        )
                    ){
                        AdminUserFinanceUploadDataRecord::addUploadRecord(
                            [
                                'user_id' => $uploadFinanceData['user_id'] , 
                                'record_id' => $uploadFinanceData['id'] ,    
                                'user_finance_data_id' => $AdminUserFinanceDataId,    
                                'status' => 0,    
                            ]
                        );
                    }    
                } 
            }
            //解析完成-设置状态
            AdminUserFinanceUploadRecord::changeStatus(
                $uploadFinanceData['id'],AdminUserFinanceUploadRecord::$stateParsed
            );
        } 
        return true ;   
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
