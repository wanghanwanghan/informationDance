<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Business\Admin\SaibopengkeAdmin\SaibopengkeAdminController;
use App\HttpController\Models\Admin\SaibopengkeAdmin\Saibopengke_Data_List_Model;
use App\HttpController\Models\Provide\RequestUserInfo;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use Carbon\Carbon;
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
        // $this->all_right_ent_txt_file_name = control::getUuid() . '.txt';
        // $this->have_null_ent_txt_file_name = 'NULL' . control::getUuid() . '.txt';
        // $this->data_desc_txt_file_name = 'DESC' . control::getUuid() . '.txt';
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
        // $Ym = Carbon::now()->format('Ym');
        // $d = 'day' . Carbon::now()->format('d');

        // is_dir($this->filePath . 'Back/' . $Ym . '/' . $d) || mkdir($this->filePath . 'Back/' . $Ym . '/' . $d, 0755, true);
        // is_dir($this->filePath . 'Work/' . $Ym . '/' . $d) || mkdir($this->filePath . 'Work/' . $Ym . '/' . $d, 0755, true);

        // $this->backPath = $this->filePath . 'Back/' . $Ym . '/' . $d . '/';
        // $this->workPath = $this->filePath . 'Work/' . $Ym . '/' . $d . '/';
        $this->workPath = $this->filePath ;

        return true;
    }

    function getFinanceOriginal($entname): ?array
    {
        $url = 'https://api.meirixindong.com/provide/v1/xd/getFinanceOriginal';
        $appId = '5BBFE57DE6DD0C8CDBC5D16A31125D5F';
        $appSecret = 'C2F24A85DF750882FAD7';
        $time = time() . mt_rand(100, 999);
        $sign = substr(strtoupper(md5($appId . $appSecret . $time)), 0, 30);

        $data = [
            'appId' => $appId,
            'time' => $time,
            'sign' => $sign,
            'entName' => $entname,
            'year' => 2020,
            'dataCount' => 3,
        ];

        return (new CoHttpClient())->useCache(true)->send($url, $data);
    }

    function readXlsx($xlsx_name)
    {
        // $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
        // $read = $excel_read->openFile($xlsx_name)->openSheet();

        $config = [
            'path' =>  $this->workPath,
        ];
        $fileName = 'new_test.xlsx';
        $xlsxObject = new \Vtiful\Kernel\Excel($config);
        $filePath = $xlsxObject->fileName($fileName, 'sheet1')
            ->header(['企业名称', '年', '字段', '数值', '区间'])->data([
                [
                    'xxx','XXXX'
                ],
                [
                    'xxx','XXXX'
                ],
            ])->output(); 

        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                CommonService::getInstance()->log4PHP('readXlsx   break');
                break;
            }

            $entname = $this->strtr_func($one[0]);
            CommonService::getInstance()->log4PHP('readXlsx   name'.$entname);
            file_put_contents(
                // $this->workPath . $this->data_desc_txt_file_name,
                $this->workPath . 'test.xlsx',
                implode('|', [ 
                    '无数据','无数据',
                ]) . PHP_EOL,
                FILE_APPEND
            );
            
            // $code = $this->strtr_func($one[1]??'');
            // $address = $this->strtr_func($one[2]??''); 

            // $f_data_info = $this->getFinanceOriginal($entname);

            // $witch_file_flag = 'right';
            $data_arr = [];

            // if (is_array($f_data_info) && !empty($f_data_info['result']['data'])) {
            //     $data_info = $f_data_info['result']['data'];
            //     $empnum_arr = [];
            //     foreach ($f_data_info['result']['otherData'] as $y=>$item){
            //         $empnum_arr[$y] = $item['EMPNUM'];
            //     }
            //     //有数字返回的
            //     foreach ($data_info as $year => $item) {
            //         is_numeric($item['VENDINC']) ? $_VENDINC = round($item['VENDINC'], 2) : $_VENDINC = '';
            //         is_numeric($item['ASSGRO']) ? $_ASSGRO = round($item['ASSGRO'], 2) : $_ASSGRO = '';
            //         is_numeric($item['MAIBUSINC']) ? $_MAIBUSINC = round($item['MAIBUSINC'], 2) : $_MAIBUSINC = '';
            //         is_numeric($item['TOTEQU']) ? $_TOTEQU = round($item['TOTEQU'], 2) : $_TOTEQU = '';
            //         is_numeric($item['RATGRO']) ? $_RATGRO = round($item['RATGRO'], 2) : $_RATGRO = '';
            //         is_numeric($item['PROGRO']) ? $_PROGRO = round($item['PROGRO'], 2) : $_PROGRO = '';
            //         is_numeric($item['NETINC']) ? $_NETINC = round($item['NETINC'], 2) : $_NETINC = '';
            //         is_numeric($item['LIAGRO']) ? $_LIAGRO = round($item['LIAGRO'], 2) : $_LIAGRO = '';
            //         is_numeric($item['SOCNUM']) ? $_SOCNUM = round($item['SOCNUM'], 2) : $_SOCNUM = '';
            //         is_numeric($empnum_arr[$year]) ? $_EMPNUM = round($empnum_arr[$year], 2) : $_EMPNUM = '';
            //         if (!is_numeric($_VENDINC) || $_VENDINC == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_ASSGRO) || $_ASSGRO == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_MAIBUSINC) || $_MAIBUSINC == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_TOTEQU) || $_TOTEQU == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_RATGRO) || $_RATGRO == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_PROGRO) || $_PROGRO == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_NETINC) || $_NETINC == 0) {
            //             $witch_file_flag = 'have_null';
            //         }
            //         if (!is_numeric($_LIAGRO) || $_LIAGRO == 0) {
            //             $witch_file_flag = 'have_null';
            //         }

            //         $data_arr[$year - 0] = [
            //             $entname,
            //             $code,
            //             $address,
            //             $year,
            //             $_VENDINC,
            //             $_ASSGRO,
            //             $_MAIBUSINC,
            //             $_TOTEQU,
            //             $_RATGRO,
            //             $_PROGRO,
            //             $_NETINC,
            //             $_LIAGRO,
            //             $_SOCNUM,
            //             $_EMPNUM
            //         ];
            //     }

            // } else {

            //     file_put_contents(
            //         $this->workPath . $this->data_desc_txt_file_name,
            //         implode('|', [
            //             $entname, $code, $address,
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //             '无数据',
            //         ]) . PHP_EOL,
            //         FILE_APPEND
            //     );

            //     Saibopengke_Data_List_Model::create()->data([
            //         'handleDate' => date('Ymd'),
            //         'filename' => $this->data_desc_txt_file_name,
            //         'descname' => $this->data_desc_txt_file_name,
            //         'entName' => $entname,
            //         'status' => 4,
            //         'responseData' => '',
            //     ])->save();

            // }

            // foreach ($data_arr as $year => $wh) {
            //     if ($witch_file_flag === 'right') {
            //         file_put_contents(
            //             $this->workPath . $this->all_right_ent_txt_file_name,
            //             implode('|', $wh) . PHP_EOL,
            //             FILE_APPEND
            //         );
            //     } else {
            //         $head = array_slice($wh, 0, 4);
            //         $temp = array_slice($wh, 4);
            //         $temp = array_map(function ($row) {
            //             return (is_numeric($row) && $row != 0) ? '正常值' : $row;
            //         }, $temp);

            //         //写摘要文件
            //         file_put_contents(
            //             $this->workPath . $this->data_desc_txt_file_name,
            //             implode('|', array_merge($head, $temp)) . PHP_EOL,
            //             FILE_APPEND
            //         );

            //         file_put_contents(
            //             $this->workPath . $this->have_null_ent_txt_file_name,
            //             implode('|', $wh) . PHP_EOL,
            //             FILE_APPEND
            //         );
            //     }
            // }

            // //入数据库之前的整理
            // $readytoinsert = [];
            // foreach ($data_arr as $year => $wh) {
            //     if ($witch_file_flag === 'right') {
            //         $readytoinsert[$entname . 'right'][] = $wh;
            //     } else {
            //         $readytoinsert[$entname][] = $wh;
            //     }
            // }

            // //入数据库
            // foreach ($readytoinsert as $ent => $val) {
            //     if (preg_match('/right/', $ent)) {
            //         $name = str_replace('right', '', $ent);
            //         Saibopengke_Data_List_Model::create()->data([
            //             'handleDate' => date('Ymd'),
            //             'filename' => $this->all_right_ent_txt_file_name,
            //             'descname' => $this->data_desc_txt_file_name,
            //             'entName' => $name,
            //             'status' => 2,
            //             'responseData' => jsonEncode($val, false),
            //         ])->save();
            //     } else {
            //         Saibopengke_Data_List_Model::create()->data([
            //             'handleDate' => date('Ymd'),
            //             'filename' => $this->have_null_ent_txt_file_name,
            //             'descname' => $this->data_desc_txt_file_name,
            //             'entName' => $ent,
            //             'status' => 3,
            //             'responseData' => jsonEncode($val, false),
            //         ])->save();
            //     }
            //     //扣15块钱
            //     $appid = (new SaibopengkeAdminController())->appid;

            //     RequestUserInfo::create()->where('appId', $appid)->update([
            //         'money' => QueryBuilder::dec(15)
            //     ]);

            // }

        }
    }

    static function getAllData(){
        $datas =  Company::create()
                        // ->field(['id','name','property2'])
                    ->field(['id'])
                    ->limit(5)
                    ->all();
        foreach($datas as $data){
            $data = array_values($data);
            yield $data;
        }
    }

    function getExcelYieldData($xlsx_name){
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
        $read = $excel_read->openFile($xlsx_name)->openSheet();

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

            $code = $this->strtr_func($one[1]??'');
            $address = $this->strtr_func($one[2]??''); 
           
            $retData =  (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getEntLianXi([
                        'entName' => $entname,
                    ])['result'];
            // $retData = LongXinService::complementEntLianXiMobileState($retData);
            // $retData = LongXinService::complementEntLianXiPosition($retData, $entname);  
            foreach($retData as $datautem){  
                // yield $datas[] = $datautem;
                yield $datas[] = array_values(array_merge(['comname' =>$entname],$datautem));
            }
        }
    }

    function run(int $taskId, int $workerIndex): bool
    {
        $startMemory = memory_get_usage(); 
        $files = glob($this->workPath.'customer_*.xlsx');
        CommonService::getInstance()->log4PHP('RunCompleteCompanyData files '.json_encode($files) );
        foreach($files as $file){
            CommonService::getInstance()->log4PHP('RunCompleteCompanyData file '.json_encode($file) );
            $excelDatas = $this->getExcelYieldData($file);
            
            $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
            CommonService::getInstance()->log4PHP('RunCompleteCompanyData 内存使用1 '.$memory .' '.$file );

            $fileName = pathinfo($file)['filename'];
            $f = fopen($this->workPath.$fileName.".csv", "w");
            fwrite($f,chr(0xEF).chr(0xBB).chr(0xBF));

            foreach ($excelDatas as $dataItem) {
                fputcsv($f, $dataItem);
            }

            $memory = round((memory_get_usage()-$startMemory)/1024/1024,3).'M'.PHP_EOL;
            CommonService::getInstance()->log4PHP('RunCompleteCompanyData 内存使用2 '.$memory .' '.$file );
            
            // @unlink($this->workPath . $file);

        }  
        
        return true ;  
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
