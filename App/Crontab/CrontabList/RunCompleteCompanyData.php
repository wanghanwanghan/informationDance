<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\LongXin\LongXinService;


class RunCompleteCompanyData extends AbstractCronTask
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

            $entname = $this->strtr_func($one[0]); 
           
            $retData =  (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getEntLianXi([
                        'entName' => $entname,
                    ])['result'];
            $retData = LongXinService::complementEntLianXiMobileState($retData);
            $retData = LongXinService::complementEntLianXiPosition($retData, $entname);  
            foreach($retData as $datautem){   
                yield $datas[] = array_values(array_merge(['comname' =>$entname],$datautem));
            }
        }
    }

    static function  testYield($tmpSiji){
        $startMemory = memory_get_usage();
        $start = microtime(true);

        // while循环执行的次数
        $nums = 1;
        //去取上一次es结果的id
        $lastId = 0;
        //每次从es取多少数据
        $size = 1000;

        //最多执行次数
        $maxRunNums =  100;
        while ($nums <= $maxRunNums ) {

            $companyEsModel = new \App\ElasticSearch\Model\Company();
            $companyEsModel
                //经营范围
                ->SetQueryBySiJiFenLei($tmpSiji)
                ->addSize($size)
                ->addSort('_id',"asc")
                //->setSource($fieldsArr)
            ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    '$lastId' => $lastId
                ])
            );
            if($lastId>0){
                $companyEsModel->addSearchAfterV1($lastId);
            }
            $companyEsModel
                ->searchFromEs() ;
            CommonService::getInstance()->log4PHP(
                json_encode([
                    __CLASS__.__FUNCTION__ .__LINE__,
                    'total value' => $companyEsModel->return_data['hits']['total']['value']
                ])
            );
            if( $companyEsModel->return_data['hits']['total']['value']<= 0){
                CommonService::getInstance()->log4PHP(
                    json_encode([
                        __CLASS__.__FUNCTION__ .__LINE__,
                        'generate data  done . memory use' => round((memory_get_usage()-$startMemory)/1024/1024,3).'M',
                        'generate data  done . costs seconds '=>microtime(true) - $start,
                        '$nums' => $nums,
                    ])
                );
                return ;
            }
            $nums ++;
            foreach($companyEsModel->return_data['hits']['hits'] as $dataItem){
                $lastId = $dataItem['_id'];
                yield $datas[] = [
                    $dataItem['_source']['ying_shou_gui_mo']
                ];
            }
        }
    }


    function run(int $taskId, int $workerIndex): bool
    {
//        return true;
        if(
            !ConfigInfo::checkCrontabIfCanRun("RunCompleteCompanyData")
        ){
            return    CommonService::getInstance()->log4PHP(__CLASS__ . ' is running RunReadAndDealXls');

        }
        CommonService::getInstance()->log4PHP(__CLASS__ . '开始');
//        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) {
//            CommonService::getInstance()->log4PHP(__CLASS__ . '开始-NO');
//            return true;
//        }
        $startMemory = memory_get_usage(); 
        
        // 找到客户名单
        $files = glob($this->workPath.'customer_*.xlsx');
        // CommonService::getInstance()->log4PHP('RunCompleteCompanyData files '.json_encode($files) );
        if(empty($files)){
            return true;
        }

        ConfigInfo::setIsRunning("RunCompleteCompanyData");

        // 一个一个的跑
        $file = pathinfo(array_shift($files))['basename'];
        // CommonService::getInstance()->log4PHP('RunCompleteCompanyData file '.($file) );
        
        // 取yield数据
        $excelDatas = $this->getYieldData($file);
        
        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        // CommonService::getInstance()->log4PHP('RunCompleteCompanyData 内存使用1 '.$memory .' '.$file );

        //写到csv里
        $fileName = pathinfo($file)['filename'];
        $f = fopen($this->workPath.$fileName.".csv", "w");
        fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($excelDatas as $dataItem) {
            fputcsv($f, $dataItem);
        }

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        // CommonService::getInstance()->log4PHP('RunCompleteCompanyData 内存使用2 '.$memory .' '.$file );

        @unlink($this->workPath . $file);
//        $this->crontabBase->removeOverlappingKey(self::getTaskName());
        ConfigInfo::setIsDone("RunCompleteCompanyData");

        return true ;  
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
