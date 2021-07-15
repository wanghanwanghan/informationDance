<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\EntDb\EntDbBasic;
use App\HttpController\Models\EntDb\EntDbInv;
use App\HttpController\Models\EntDb\EntDbModify;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\Zip\ZipService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;
use Swoole\Coroutine;

class ZhangJiangProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        CommonService::getInstance()->log4PHP('zhangjiang start : ' . Carbon::now()->format('Y-m-d H:i:s'));

        if ($dh = opendir(TEMP_FILE_PATH)) {
            while (false !== ($file = readdir($dh))) {
                if (strpos($file, 'zip') !== false) {
                    $filename_arr = ZipService::getInstance()
                        ->unzip(TEMP_FILE_PATH . $file, TEMP_FILE_PATH);
                    if (!empty($filename_arr)) {
                        $this->handleFileArr($filename_arr);
                    }
                }
            }
        }
        closedir($dh);
    }

    function handleFileArr($filename_arr): void
    {
        foreach ($filename_arr as $filename) {
            if (preg_match('/^basic/', $filename) || preg_match('/企业基本信息\(变更\)/', $filename) || preg_match('/基本信息\(新增\)/', $filename)) {
                $this->handleBasic($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^inv_\d+/', $filename) || preg_match('/股东及出资信息\(变更\)/', $filename)) {
                $this->handleInv($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^inv_new_\d+/', $filename) || preg_match('/股东及出资信息\(新增\)/', $filename)) {
                $this->handleInvNew($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^history_inv_\d+/', $filename)) {
                $this->handleInvHistory($this->readCsv($filename));
            }
        }
        foreach ($filename_arr as $filename) {
            if (preg_match('/^modify_\d+/', $filename) || preg_match('/企业变更信息/', $filename)) {
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
        foreach ($arr as $key => $val) {
            if ($key === 0) continue;
            $insert = [
                'ENTNAME' => $val[0],
                'INV' => $val[1],
                'SHXYDM' => $val[2],
                'INVTYPE' => $val[3],
                'SUBCONAM' => $val[4],
                'CONCUR' => $val[5],
                'CONRATIO' => $val[6],
                'CONDATE' => $val[7],
                'CHANGE_TYPE' => $val[8],
            ];
            if ($this->needContinue(__FUNCTION__, $insert)) continue;
            $check = EntDbInv::create()->where([
                'ENTNAME' => $val[0],
                'INV' => $val[1],
                'SHXYDM' => $val[2],
            ])->get();
            try {
                if (empty($check)) {
                    EntDbInv::create()->data($insert)->save();
                } else {
                    unset($insert['ENTNAME']);
                    unset($insert['INV']);
                    unset($insert['SHXYDM']);
                    EntDbInv::create()->where([
                        'ENTNAME' => $val[0],
                        'INV' => $val[1],
                        'SHXYDM' => $val[2],
                    ])->update($insert);
                }
            } catch (\Throwable $e) {
                $this->writeErr($e);
            }
        }
    }

    function handleInvNew($arr): void
    {
        foreach ($arr as $key => $val) {
            if ($key === 0) continue;
            $insert = [
                'ENTNAME' => $val[0],
                'INV' => $val[1],
                'SHXYDM' => $val[2],
                'INVTYPE' => $val[3],
                'SUBCONAM' => $val[4],
                'CONCUR' => $val[5],
                'CONRATIO' => $val[6],
                'CONDATE' => $val[7],
                'CHANGE_TYPE' => $val[8],
            ];
            if ($this->needContinue(__FUNCTION__, $insert)) continue;
            $check = EntDbInv::create()->where([
                'ENTNAME' => $val[0],
                'INV' => $val[1],
                'SHXYDM' => $val[2],
            ])->get();
            try {
                if (empty($check)) {
                    EntDbInv::create()->data($insert)->save();
                } else {
                    unset($insert['ENTNAME']);
                    unset($insert['INV']);
                    unset($insert['SHXYDM']);
                    EntDbInv::create()->where([
                        'ENTNAME' => $val[0],
                        'INV' => $val[1],
                        'SHXYDM' => $val[2],
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

    protected function onPipeReadable(Process $process)
    {
        parent::onPipeReadable($process);

        return true;
    }

    protected function onShutDown()
    {
    }

    protected function onException(\Throwable $throwable, ...$args)
    {
        $this->writeErr($throwable);
    }


}
