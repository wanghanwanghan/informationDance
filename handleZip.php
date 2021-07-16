<?php

use App\HttpController\Models\EntDb\EntDbBasic;
use App\HttpController\Models\EntDb\EntDbInv;
use App\HttpController\Models\EntDb\EntDbModify;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\Zip\ZipService;
use \EasySwoole\EasySwoole\Core;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\Common\CommonService;
use \EasySwoole\Component\Process\Config;
use \EasySwoole\Component\Process\AbstractProcess;
use Swoole\Coroutine;

require_once './vendor/autoload.php';

Core::getInstance()->initialize();

class P extends AbstractProcess
{
    protected function run($arg)
    {
        $zip_file_arr = [];

        if ($dh = opendir(TEMP_FILE_PATH)) {
            while (false !== ($file = readdir($dh))) {
                if (false !== strpos($file, 'zip')) {
                    array_push($zip_file_arr, $file);
                }
            }
        }

        closedir($dh);

        //处理zip
        foreach ($zip_file_arr as $file) {
            $filename_arr = ZipService::getInstance()
                ->unzip(TEMP_FILE_PATH . $file, TEMP_FILE_PATH);
            if (!empty($filename_arr)) {
                CommonService::getInstance()->log4PHP('==========读到的zip文件 ' . $file . ' ==========');
                //开始处理csv
                $this->handleFileArr($filename_arr);
                //删除对应的zip和csv
                $this->delFile(TEMP_FILE_PATH, $file);
            }
        }

        CommonService::getInstance()->log4PHP('zip 全部处理完成');
    }

    function delFile($dir, $zip_name): bool
    {
        if (strpos($dir, 'informationDance') === false) return true;

        if (empty($zip_name)) return true;

        if ($dh = opendir($dir)) {
            while (false !== ($file = readdir($dh))) {
                if (strlen($file) < 5) {
                    continue;
                }
                if (strpos($file, 'csv') !== false) {
                    unlink($dir . $file);
                }
                if ($file === $zip_name) {
                    unlink($dir . $file);
                }
            }
        }

        closedir($dh);

        return true;
    }

    function handleFileArr($filename_arr): void
    {
        foreach ($filename_arr as $filename) {
            if (preg_match('/^basic/', $filename) || preg_match('/^企业基本信息\(变更\)/', $filename) || preg_match('/^基本信息\(新增\)/', $filename)) {
                $this->handleBasic($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^inv_\d+/', $filename) || preg_match('/^股东及出资信息\(变更\)/', $filename)) {
                $this->handleInv($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^inv_new_\d+/', $filename) || preg_match('/^股东及出资信息\(新增\)/', $filename)) {
                $this->handleInv($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^history_inv_\d+/', $filename)) {
                $this->handleInvHistory($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^modify_\d+/', $filename) || preg_match('/^企业变更信息/', $filename)) {
                $this->handleModify($this->readCsv($filename));
            }
        }
    }

    function readCsv($filename): \Generator
    {
        CommonService::getInstance()->log4PHP($filename, 'info', 'zhangjiang.log');
        $handle = fopen(TEMP_FILE_PATH . $filename, 'rb');
        while (feof($handle) === false) {
            yield fgetcsv($handle);
        }
        fclose($handle);
    }

    function needContinue($handleName, $data): bool
    {
        if (empty($data['ENTNAME'])) return true;
        if (empty($data['SHXYDM'])) return true;

        return false;
    }

    function handleBasic($arr): void
    {
        foreach ($arr as $key => $val) {
            if ($key === 0) continue;
            $insert = [
                'ENTNAME' => $val[0],
                'OLDNAME' => $val[1],
                'SHXYDM' => $val[2],
                'FRDB' => $val[3],
                'ESDATE' => $val[4],
                'ENTSTATUS' => $val[5],
                'REGCAP' => $val[6],
                'REGCAPCUR' => $val[7],
                'DOM' => $val[8],
                'ENTTYPE' => $val[9],
                'OPSCOPE' => $val[10],
                'REGORG' => $val[11],
                'OPFROM' => $val[12],
                'OPTO' => $val[13],
                'APPRDATE' => $val[14],
                'ENDDATE' => $val[15],
                'REVDATE' => $val[16],
                'CANDATE' => $val[17],
                'JWD' => $val[18],
                'INDUSTRY' => $val[19],
                'INDUSTRY_CODE' => $val[20],
                'PROVINCE' => $val[21],
                'ORGID' => $val[22],
                'ENGNAME' => $val[23],
                'WEBSITE' => $val[24],
                'CHANGE_TYPE' => $val[25],
            ];
            if ($this->needContinue(__FUNCTION__, $insert)) continue;
            $check = EntDbBasic::create()->where('SHXYDM', $val[2])->get();
            try {
                if (empty($check)) {
                    EntDbBasic::create()->data($insert)->save();
                } else {
                    unset($insert['SHXYDM']);
                    EntDbBasic::create()->where('SHXYDM', $val[2])->update($insert);
                }
            } catch (\Throwable $e) {
                $this->writeErr($e);
            }
        }
    }

    function handleInv($arr): void
    {
        $add = 0;
        foreach ($arr as $key => $val) {
            if ($key === 0) {
                //傻逼数据格式不统一
                current($val) === 'PROVINCE' ? $add = 1 : $add = 0;
                continue;
            }
            $insert = [
                'ENTNAME' => $val[0 + $add],
                'INV' => $val[1 + $add],
                'SHXYDM' => $val[2 + $add],
                'INVTYPE' => $val[3 + $add],
                'SUBCONAM' => $val[4 + $add],
                'CONCUR' => $val[5 + $add],
                'CONRATIO' => $val[6 + $add],
                'CONDATE' => $val[7 + $add],
                'CHANGE_TYPE' => $val[8 + $add],
            ];
            if ($this->needContinue(__FUNCTION__, $insert)) continue;
            $check = EntDbInv::create()->where([
                'ENTNAME' => $val[0 + $add],
                'INV' => $val[1 + $add],
                'SHXYDM' => $val[2 + $add],
            ])->get();
            try {
                if (empty($check)) {
                    EntDbInv::create()->data($insert)->save();
                } else {
                    unset($insert['ENTNAME']);
                    unset($insert['INV']);
                    unset($insert['SHXYDM']);
                    EntDbInv::create()->where([
                        'ENTNAME' => $val[0 + $add],
                        'INV' => $val[1 + $add],
                        'SHXYDM' => $val[2 + $add],
                    ])->update($insert);
                }
            } catch (\Throwable $e) {
                $this->writeErr($e);
            }
        }
    }

    function handleInvHistory($arr): void
    {
        foreach ($arr as $key => $val) {
            if ($key === 0) continue;
            $del = [
                'ENTNAME' => $val[0],
                'SHXYDM' => $val[1],
                'INV' => $val[2],
            ];
            if ($this->needContinue(__FUNCTION__, $del)) continue;
            $check = EntDbInv::create()->where($del)->get();
            try {
                if (!empty($check)) {
                    EntDbInv::create()->destroy($del);
                }
            } catch (\Throwable $e) {
                $this->writeErr($e);
            }
        }
    }

    function handleModify($arr): void
    {
        foreach ($arr as $key => $val) {
            if ($key === 0) continue;
            $insert = [
                'ENTNAME' => $val[0],
                'ALTITEM' => $val[1],
                'ALTBE' => $val[2],
                'ALTAF' => $val[3],
                'ALTDATE' => trim(str_replace(['\\', '/'], '-', $val[4])),
            ];
            if (empty($val[0])) continue;
            try {
                EntDbModify::create()->data($insert)->save();
            } catch (\Throwable $e) {
                $this->writeErr($e);
            }
        }
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

CreateDefine::getInstance()->createDefine(__DIR__);
CreateConf::getInstance()->create(__DIR__);
CreateMysqlPoolForProjectDb::getInstance()->createMysql();
CreateMysqlPoolForEntDb::getInstance()->createMysql();
CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();
CreateMysqlOrm::getInstance()->createMysqlOrm();
CreateMysqlOrm::getInstance()->createEntDbOrm();

$conf = new Config();

$conf->setEnableCoroutine(true);

$process = new P($conf);

$process->getProcess()->start();

while (Swoole\Process::wait(true)) {
    var_dump('exit eee');
    die();
}
