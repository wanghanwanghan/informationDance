<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\AdminNew\ConfigInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\JinCaiShuKe\JinCaiShuKeService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\Mysqli\QueryBuilder;
use wanghanwanghan\someUtils\control;
use App\HttpController\Models\RDS3\Company;
use App\HttpController\Service\ChuangLan\ChuangLanService;
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

    function getYieldDataCheckMobileV2($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $i = 1;
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
            $mobileStr = str_replace(";", ",", trim($value2));
            $newmobileStr = "";
            if(!empty($mobileStr)){
                $res = (new ChuangLanService())->getCheckPhoneStatus([
                    'mobiles' => $mobileStr,
                ]);
                // $res['data'] = LongXinService::shiftArrayKeys($res['data'], 'mobile');
                if (!empty($res['data'])){// $res['data']还能是空呢?
                    foreach($res['data'] as $dataItem){
                        if($dataItem['status'] == 1){
                            $newmobileStr .= $dataItem["mobile"].';';
                        }
                    }
                }
            }

            if ($i%100==0){
                CommonService::getInstance()->log4PHP($xlsx_name.json_encode(
                        [
                            'value' => [$value2],
                            'params' => $mobileStr,
                            'res' => $res,
                            'num' =>  $i
                        ]
                    ));
            }

//            sleep(10);
            $i ++;
            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                $newmobileStr
            ];
        }
    }


    function jincai($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
        $excel_read->openFile($xlsx_name)->openSheet();

        $datas = [];
        $i = 1;
        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            $value0 = $this->strtr_func($one[0]);//任务号
            $value1 = $this->strtr_func($one[1]);//信用代码
            $value2 = $this->strtr_func($one[2]);
            $mobileStr = str_replace(";", ",", trim($value2));
            $newmobileStr = "";
            $postData = [
                'nsrsbh' => $value1,
                'rwh' => $value0,
                'page' => 1,
                'pageSize' => 10,
            ];
            $res =
            (new JinCaiShuKeService())
                ->setCheckRespFlag(true)
                ->S000523($postData['nsrsbh'], $postData['rwh'], $postData['page'], $postData['pageSize']);

            CommonService::getInstance()->log4PHP($xlsx_name.json_encode(
                    [
                        '$postData' => $postData,
                        '$res' => $res,
                        'num' =>  $i
                    ]
                ));
            $i ++;
            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                json_encode($res['result']['content']),
                json_encode($res)
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
            //$tmpRes = (new XinDongService())->matchContactNameByWeiXinName($value0,$value2);
            $tmpRes = (new XinDongService())->matchContactNameByWeiXinNameV2($value0,$value2);

             CommonService::getInstance()->log4PHP('matchContactNameByWeiXinName'.json_encode(
                 [
                     'value' => [$value0,$value2],
                     'params' => $value0,
                     'res' => $tmpRes
                 ]
             ));
            yield $datas[] = [
                $value0,
                $value1,
                $value2,
                $tmpRes['data']['stff_name'],
                $tmpRes['data']['staff_type_name'],
                $tmpRes['match_res']['type'],
                $tmpRes['match_res']['details'],
                $tmpRes['match_res']['percentage'],
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
        ConfigInfo::setIsDone("RunReadAndDealXls");
        return true ;
    }

    function matchWeiXinName($file,$debugLog){
        $startMemory = memory_get_usage();

        // 取yield数据 
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $newFile = 'dealing_'.$file;
        rename($this->workPath.$file, $this->workPath.$newFile);

        $excelDatas = $this->getYieldDataToMathWeiXin($newFile);

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

        @unlink($this->workPath . $newFile);
        ConfigInfo::setIsDone("RunReadAndDealXls");
        return true ;
    }

    function checkMobile($file,$debugLog){
        $startMemory = memory_get_usage();

        // 取yield数据 
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $excelDatas = $this->getYieldDataCheckMobile($file);

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
        ConfigInfo::setIsDone("RunReadAndDealXls");
        return true ;
    }

    function checkMobileV2($file,$debugLog){
        $startMemory = memory_get_usage();
        $newFile = 'dealing_'.$file;
        rename($this->workPath.$file, $this->workPath.$newFile);

        // 取yield数据 
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $excelDatas = $this->getYieldDataCheckMobileV2($newFile);

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用1 '.$memory .' '.$newFile );

        //写到csv里 
        $fileName = pathinfo($file)['filename'];
        $f = fopen($this->workPath.$fileName.".csv", "w");
        fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($excelDatas as $dataItem) {
            fputcsv($f, $dataItem);
        }

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用2 '.$memory .' '.$newFile );

        @unlink($this->workPath . $newFile);
        ConfigInfo::setIsDone("RunReadAndDealXls");
        return true ;
    }

    function getjincaiData($file,$debugLog){
        $startMemory = memory_get_usage();
        $newFile = 'dealing_'.$file;
        rename($this->workPath.$file, $this->workPath.$newFile);

        // 取yield数据
        // $excelDatas = $this->getYieldData($file,'matchNameFormatData');
        $excelDatas = $this->jincai($newFile);

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用1 '.$memory .' '.$newFile );

        //写到csv里
        $fileName = pathinfo($file)['filename'];
        $f = fopen($this->workPath.$fileName.".csv", "w");
        fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

        foreach ($excelDatas as $dataItem) {
            fputcsv($f, $dataItem);
        }

        $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
        $debugLog && CommonService::getInstance()->log4PHP('matchName 内存使用2 '.$memory .' '.$newFile );

        @unlink($this->workPath . $newFile);
        ConfigInfo::setIsDone("RunReadAndDealXls");
        return true ;
    }

    function run(int $taskId, int $workerIndex): bool
    {

        $debugLog = true;

        // 找到需要处理的文件 uploadAndDealXls_matchName_测试
        $files = glob($this->workPath.'uploadAndDealXls_*.xlsx');
//        $debugLog && CommonService::getInstance()->log4PHP('RunReadAndDealXls files '.json_encode($files) );
        if(empty($files)){
            return true;
        }

        // 一个一个的跑

        $file = pathinfo(array_shift($files))['basename'];
        $fileName = pathinfo($file)['filename'];
//        $debugLog &&  CommonService::getInstance()->log4PHP('uploadAndDealXls_ file '.($file) );
        $fileNameArr = explode('_',$fileName);

        // 匹配企业名称
        if($fileNameArr[1] == 'matchName'){
//            return true;
//            $debugLog &&  CommonService::getInstance()->log4PHP('matchName  '.($file) );
            return $this->matchName($file,$debugLog);
        }

        // 匹配微信吗名
        if($fileNameArr[1] == 'matchWeiXinName'){
//            return true;
//            $debugLog &&  CommonService::getInstance()->log4PHP('matchWeiXinName  '.($file) );
            return $this->matchWeiXinName($file,$debugLog);
        }

        // 校验手机号
        if($fileNameArr[1] == 'checkMobile'){
//            return true;
//            $debugLog &&  CommonService::getInstance()->log4PHP('matchWeiXinName  '.($file) );
           // return $this->checkMobile($file,$debugLog);
        }

        // 校验手机号
        if($fileNameArr[1] == 'checkMobileV2'){
//            $debugLog &&  CommonService::getInstance()->log4PHP('matchWeiXinName  '.($file) );
            return $this->checkMobileV2($file,$debugLog);
        }


        //  jincai
        if($fileNameArr[1] == 'jincai'){
//            $debugLog &&  CommonService::getInstance()->log4PHP('matchWeiXinName  '.($file) );
            return $this->getjincaiData($file,$debugLog);
        }

        return true ;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
