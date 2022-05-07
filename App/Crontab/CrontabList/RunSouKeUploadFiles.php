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

class RunSouKeUploadFiles extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/TempWork/SouKe/';
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
    function createDir(): bool
    {
        $Ym = Carbon::now()->format('Ym'); 

        is_dir($this->filePath . 'Back/' . $Ym ) || mkdir($this->filePath . 'Back/' . $Ym , 0755, true);
        is_dir($this->filePath . 'Work/' . $Ym) || mkdir($this->filePath . 'Work/' . $Ym , 0755, true);

        $this->backPath = $this->filePath . 'Back/' . $Ym . '/';
        $this->workPath = $this->filePath . 'Work/' . $Ym . '/';
        return true;
    }

    static function getRule(): string
    {
        return '*/1 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }
 

    function readXlsx($xlsx_name)
    {
        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]); 

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
            CommonService::getInstance()->log4PHP(
                '[souKe]- readXlsx['.json_encode(
                    [
                        $entname,
                        $code,
                        $address,
                    ]
                ).']'
            );

        }
    }

    function run(int $taskId, int $workerIndex): bool
    {
       

        $ignore = ['.', '..', '.gitignore'];

        if ($dh = opendir($this->workPath)) {
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    if (strpos($file, '.xlsx') !== false) {
                        $this->readXlsx($file); 
                    }
                }
            }
        }
        closedir($dh);

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
