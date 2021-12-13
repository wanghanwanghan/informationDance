<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class RunSaiMengHuiZhiCaiWu extends AbstractCronTask
{
    public $crontabBase;
    public $filePath = ROOT_PATH . '/TempWork/SaiMengHuiZhi/';
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
        $this->all_right_ent_txt_file_name = control::getUuid() . '.txt';
        $this->have_null_ent_txt_file_name = control::getUuid() . '.txt';
        $this->data_desc_txt_file_name = control::getUuid() . '.txt';
    }

    static function getRule(): string
    {
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function createDir(): bool
    {
        $Ym = Carbon::now()->format('Ym');
        $d = 'day' . Carbon::now()->format('d');

        is_dir($this->filePath . 'Back/' . $Ym . '/' . $d) || mkdir($this->filePath . 'Back/' . $Ym . '/' . $d, 0755, true);
        is_dir($this->filePath . 'Work/' . $Ym . '/' . $d) || mkdir($this->filePath . 'Work/' . $Ym . '/' . $d, 0755, true);

        $this->backPath = $this->filePath . 'Back/' . $Ym . '/' . $d . '/';
        $this->workPath = $this->filePath . 'Work/' . $Ym . '/' . $d . '/';

        return true;
    }

    function moveFileToBack($filename): bool
    {


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
            'dataCount' => 3,
        ];

        return (new CoHttpClient())->useCache(true)->send($url, $data);
    }

    function readXlsx($xlsx_name)
    {
        CommonService::getInstance()->log4PHP("准备打开的文件名称 : {$xlsx_name}");

        $excel_read = new \Vtiful\Kernel\Excel(['path' => $this->workPath]);
        $read = $excel_read->openFile($xlsx_name)->openSheet();

        CommonService::getInstance()->log4PHP("excel打开flag : {$read}");

        while (true) {

            $one = $excel_read->nextRow([
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
                \Vtiful\Kernel\Excel::TYPE_STRING,
            ]);

            if (empty($one)) {
                break;
            }

            CommonService::getInstance()->log4PHP('行数据');
            CommonService::getInstance()->log4PHP($one);

            $entname = $this->strtr_func($one[0]);
            $code = $this->strtr_func($one[1]);
            $address = $this->strtr_func($one[2]);

            //  `VENDINC` decimal(20,2) DEFAULT NULL COMMENT '营业总收入',
            //  `ASSGRO` decimal(20,2) DEFAULT NULL COMMENT '资产总额',
            //  `MAIBUSINC` decimal(20,2) DEFAULT NULL COMMENT '主营业务收入',
            //  `TOTEQU` decimal(20,2) DEFAULT NULL COMMENT '所有者权益',
            //  `RATGRO` decimal(20,2) DEFAULT NULL COMMENT '纳税总额',
            //  `PROGRO` decimal(20,2) DEFAULT NULL COMMENT '利润总额',
            //  `NETINC` decimal(20,2) DEFAULT NULL COMMENT '净利润',
            //  `LIAGRO` decimal(20,2) DEFAULT NULL COMMENT '负债总额',
            //  `SOCNUM`

            $f_data_info = $this->getFinanceOriginal($entname);

            $witch_file_flag = 'right';
            $data_arr = [];

            if (is_array($f_data_info) && !empty($f_data_info['result'])) {

                //有数字返回的
                foreach ($f_data_info['result'] as $year => $item) {
                    is_numeric($item['VENDINC']) ? $_VENDINC = round($item['VENDINC'], 2) : $_VENDINC = '';
                    is_numeric($item['ASSGRO']) ? $_ASSGRO = round($item['ASSGRO'], 2) : $_ASSGRO = '';
                    is_numeric($item['MAIBUSINC']) ? $_MAIBUSINC = round($item['MAIBUSINC'], 2) : $_MAIBUSINC = '';
                    is_numeric($item['TOTEQU']) ? $_TOTEQU = round($item['TOTEQU'], 2) : $_TOTEQU = '';
                    is_numeric($item['RATGRO']) ? $_RATGRO = round($item['RATGRO'], 2) : $_RATGRO = '';
                    is_numeric($item['PROGRO']) ? $_PROGRO = round($item['PROGRO'], 2) : $_PROGRO = '';
                    is_numeric($item['NETINC']) ? $_NETINC = round($item['NETINC'], 2) : $_NETINC = '';
                    is_numeric($item['LIAGRO']) ? $_LIAGRO = round($item['LIAGRO'], 2) : $_LIAGRO = '';
                    is_numeric($item['SOCNUM']) ? $_SOCNUM = round($item['SOCNUM'], 2) : $_SOCNUM = '';

                    if (!is_numeric($_VENDINC) || $_VENDINC == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_ASSGRO) || $_ASSGRO == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_MAIBUSINC) || $_MAIBUSINC == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_TOTEQU) || $_TOTEQU == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_RATGRO) || $_RATGRO == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_PROGRO) || $_PROGRO == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_NETINC) || $_NETINC == 0) {
                        $witch_file_flag = 'have_null';
                    }
                    if (!is_numeric($_LIAGRO) || $_LIAGRO == 0) {
                        $witch_file_flag = 'have_null';
                    }

                    $data_arr[$year - 0] = [
                        $entname,
                        $code,
                        $address,
                        $year,
                        $_VENDINC,
                        $_ASSGRO,
                        $_MAIBUSINC,
                        $_TOTEQU,
                        $_RATGRO,
                        $_PROGRO,
                        $_NETINC,
                        $_LIAGRO,
                        $_SOCNUM,
                    ];
                }

            } else {

                file_put_contents(
                    $this->workPath . $this->have_null_ent_txt_file_name,
                    implode('|', [
                        $entname, $code, $address,
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                        '无数据',
                    ]) . PHP_EOL,
                    FILE_APPEND
                );

            }

            foreach ($data_arr as $year => $wh) {
                if ($witch_file_flag === 'right') {
                    file_put_contents(
                        $this->workPath . $this->all_right_ent_txt_file_name,
                        implode('|', $wh) . PHP_EOL,
                        FILE_APPEND
                    );
                } else {

                    $head = array_slice($wh, 0, 4);
                    $temp = array_slice($wh, 4);
                    $temp = array_map(function ($row) {
                        return (is_numeric($row) && $row != 0) ? '正常值' : $row;
                    }, $temp);

                    //写摘要文件
                    file_put_contents(
                        $this->workPath . $this->data_desc_txt_file_name,
                        implode('|', array_merge($head, $temp)) . PHP_EOL,
                        FILE_APPEND
                    );

                    file_put_contents(
                        $this->workPath . $this->have_null_ent_txt_file_name,
                        implode('|', $wh) . PHP_EOL,
                        FILE_APPEND
                    );
                }
            }

        }
    }

    function run(int $taskId, int $workerIndex): bool
    {
        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) {
            CommonService::getInstance()->log4PHP(__CLASS__ . '不开始');
            return true;
        }

        $ignore = ['.', '..', '.gitignore'];

        if ($dh = opendir($this->workPath)) {
            while (false !== ($file = readdir($dh))) {
                if (!in_array($file, $ignore, true)) {
                    if (strpos($file, '.xlsx') !== false) {
                        CommonService::getInstance()->log4PHP("准备处理的文件 : {$file}");
                        $this->readXlsx($file);
                        file_put_contents($this->backPath . $file, file_get_contents($this->workPath . $file));
                        if (strpos($this->workPath . $file, '.xlsx') !== false) {
                            @unlink($this->workPath . $file);
                        }
                    }
                }
            }
        }
        closedir($dh);

        return $this->crontabBase->removeOverlappingKey(self::getTaskName());
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

}
