<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\BusinessBase\Company287Model;
use App\HttpController\Service\Common\CommonService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class FillEntAllField extends AbstractCronTask
{
    private $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex): bool
    {
        // $workerIndex是task进程编号
        // taskId是进程周期内第几个task任务
        // 可以用task，也可以用process

        // TEMP_FILE_PATH 目录下 处理 特定前缀 的文件
        // 文件前缀 fill_ent_all_field

        $prefix = 'fill_ent_all_field';
        $len = strlen($prefix);

        if ($this->crontabBase->withoutOverlapping(self::getTaskName(), 3200)) {
            // 遍历文件夹
            if ($dh = opendir(TEMP_FILE_PATH)) {
                while (false !== ($file = readdir($dh))) {
                    if (mb_substr($file, 0, $len) === $prefix) {
                        //有这个文件前缀 并且 也有 success_ 表示已经执行 或者 正在执行
                        $check = glob(TEMP_FILE_PATH . 'success*' . $file);
                        if (!empty($check)) {
                            continue;
                        }
                        $fp_r = fopen(TEMP_FILE_PATH . $file, 'r');
                        $fp_w = fopen(TEMP_FILE_PATH . 'success_' . $file, 'w+');
                        while (feof($fp_r) === false) {
                            $entname_info_str = trim(fgets($fp_r));
                            if (empty($entname_info_str)) break;
                            if (preg_match('/\t/', $entname_info_str)) {
                                $entname_info = explode("\t", $entname_info_str);
                            } else {
                                $entname_info = explode(',', $entname_info_str);
                            }
                            $entname = trim($entname_info[0]);
                            if (empty($entname)) {
                                fwrite($fp_w, $entname . PHP_EOL);
                                continue;
                            }
                            $code = trim($entname_info[1] ?? '');
                            $info = Company287Model::create()->where('property1', $code)->get();
                            if (empty($info)) {
                                $info = Company287Model::create()->where('name', $entname)->get();
                                if (empty($info)) {
                                    $content = $entname;
                                } else {
                                    $content = implode('|', obj2Arr($info));
                                }
                            } else {
                                $content = implode('|', obj2Arr($info));
                            }
                            fwrite($fp_w, $content . PHP_EOL);
                        }
                    }
                }
            }
            closedir($dh);
            $this->crontabBase->removeOverlappingKey(self::getTaskName());
        }

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString());
    }

    function writeErr(\Throwable $e): void
    {
        $file = $e->getFile();
        $line = $e->getLine();
        $msg = $e->getMessage();
        $content = "[file ==> {$file}] [line ==> {$line}] [msg ==> {$msg}]";
        CommonService::getInstance()->log4PHP($content);
    }
}
