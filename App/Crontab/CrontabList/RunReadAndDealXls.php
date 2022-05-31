<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;
use App\HttpController\Service\XinDong\XinDongService;
use App\HttpController\Service\CreateConf;


class RunReadAndDealXls extends AbstractCronTask
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
            $tmpData = (new XinDongService())->matchEntByName($value0,1,4);
            CommonService::getInstance()->log4PHP('matchNamXXXX'.json_encode(
                [
                    'value' => [$value0,$value1],
                    'params' => $value0,
                    'res' => $tmpData
                ]
            )); 
            yield $datas[] = [
                $value0,
                $tmpData['id'], 
                $tmpData['name'], 
            ];
        }
    }

    function getYieldDataToMathWeiXin($xlsx_name){
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

            //企业名称
            $value0 = $this->strtr_func($one[0]);  
            //手机号
            $value1 = $this->strtr_func($one[1]);  
            //微信名
            $value2 = $this->strtr_func($one[2]);  
            $value3 = $this->strtr_func($one[3]);   
            $tmpRes = (new XinDongService())->matchContactNameByWeiXinName($value0,$value2);
            CommonService::getInstance()->log4PHP('matchContactNameByWeiXinName'.json_encode(
                [
                    'value' => [$value0,$value1],
                    'params' => $value0,
                    'res' => $tmpRes
                ]
            )); 
            yield $datas[] = [
                $value0,
                $value1, 
                $value2, 
                $tmpRes
            ];
        }
    }

    // function matchNameFormatData($tmpDataItem){
    //      $res = (new XinDongService())->matchEntByName($tmpDataItem[0],1,4.5);
    //      CommonService::getInstance()->log4PHP('matchNamYYYY'.json_encode($res)); 
    //      return $$res;
    // }
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

    function matchWeiXinName($file,$debugLog){
        $startMemory = memory_get_usage(); 

        // 取yield数据 
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $excelDatas = $this->getYieldDataToMathWeiXin($file); 

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

    function run(int $taskId, int $workerIndex): bool
    {
         
        $debugLog = true; 
        
        // 找到需要处理的文件 uploadAndDealXls_matchName_测试
        $files = glob($this->workPath.'uploadAndDealXls_*.xlsx');
        $debugLog && CommonService::getInstance()->log4PHP('RunReadAndDealXls files '.json_encode($files) );
        if(empty($files)){
            return true;
        }
         
        // 一个一个的跑
        $file = pathinfo(array_shift($files))['basename'];
        $fileName = pathinfo($file)['filename'];
        $debugLog &&  CommonService::getInstance()->log4PHP('uploadAndDealXls_ file '.($file) );
        $fileNameArr = explode('_',$fileName);
        
        // 匹配企业名称
        if($fileNameArr[1] == 'matchName'){ 
            $debugLog &&  CommonService::getInstance()->log4PHP('matchName  '.($file) );
            return $this->matchName($file,$debugLog);
        }

        // 匹配微信吗名
        if($fileNameArr[1] == 'matchWeiXinName'){ 
            $debugLog &&  CommonService::getInstance()->log4PHP('matchWeiXinName  '.($file) );
            return $this->matchWeiXinName($file,$debugLog);
        }

        return true ;   
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
