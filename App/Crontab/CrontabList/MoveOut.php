<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\EntDb\EntDbBasic;
use App\HttpController\Models\EntDb\EntDbInv;
use App\HttpController\Models\EntDb\EntDbModify;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\MoveOut\MoveOutService;
use App\HttpController\Service\Zip\ZipService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class MoveOut extends AbstractCronTask
{
    private $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每天的凌晨3点
        return '0 22 * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex): bool
    {
        //$workerIndex是task进程编号
        //taskId是进程周期内第几个task任务
        //可以用task，也可以用process

        CommonService::getInstance()->log4PHP([
            'move out start : ' . Carbon::now()->format('Y-m-d H:i:s')
        ]);

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) {
            CommonService::getInstance()->log4PHP(__CLASS__ . '不开始');
            return true;
        }

        $target_time = Carbon::now()->subDays(1)->format('Ymd');

        $sendHeaders['authorization'] = $this->createToken();

        $data = [
            'usercode' => 'j7uSz7ipmJ'
        ];

        $url = 'http://39.106.95.155/data/daily_ent_mrxd/?t=' . time();

        $res = (new CoHttpClient())->send($url, $data, $sendHeaders);

        if ($res['code'] - 0 === 200 && is_array($res['data']) && !empty($res['data'])) {
            foreach ($res['data'] as $one) {
                $state = $one['state'] - 0;
                //返回错误
                if ($state !== 1) continue;
                $name = $one['name'];
                //不是前一天的
                if (strpos($name, $target_time) === false) continue;
                $load_url = $one['load_url'];
                $this->getFileByWget($load_url, TEMP_FILE_PATH, $name);
                $filename_arr = ZipService::getInstance()->unzip(TEMP_FILE_PATH . $name, TEMP_FILE_PATH);
                if (!empty($filename_arr)) $this->handleFileArr($filename_arr);
            }
        }

        $this->delFileByCtime(TEMP_FILE_PATH, 5);

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

        //更新所有监控中的企业
        MoveOutService::getInstance()->updateDatabase();

        CommonService::getInstance()->log4PHP([
            'move out stop : ' . Carbon::now()->format('Y-m-d H:i:s')
        ]);

        return true;
    }

    function readCsv($filename): \Generator
    {
        $handle = fopen(TEMP_FILE_PATH . $filename, 'rb');
        while (feof($handle) === false) {
            yield fgetcsv($handle);
        }
        fclose($handle);
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

    //删除n天前创建的文件
    function delFileByCtime($dir, $n = 10, $ignore = []): bool
    {
        if (strpos($dir, 'informationDance') === false) return true;

        $ignore = array_merge($ignore, ['.', '..', '.gitignore']);

        if (is_dir($dir) && is_numeric($n)) {
            if ($dh = opendir($dir)) {
                while (false !== ($file = readdir($dh))) {
                    if (!in_array($file, $ignore, true)) {
                        $fullpath = $dir . $file;
                        if (is_dir($fullpath)) {
                            if (count(scandir($fullpath)) == 2) {
                                //rmdir($fullpath);
                                CommonService::getInstance()->log4PHP("rmdir {$fullpath}");
                            } else {
                                $this->delFileByCtime($fullpath, $n, $ignore);
                            }
                        } else {
                            $filedate = filectime($fullpath);
                            $day = round((time() - $filedate) / 86400);
                            if ($day >= $n) {
                                unlink($fullpath);
                            }
                        }
                    }
                }
            }
            closedir($dh);
        }

        return true;
    }

    function getFileByWget($url, $dir, $name, $ext = '.zip'): bool
    {
        $file_name = $dir . $name . $ext;
        $commod = "wget -q {$url} -O {$file_name}";
        system($commod);

        // 这里顺便给火眼发过去
        // todo

        return true;
    }

    function createToken()
    {
        $params = ['usercode' => 'j7uSz7ipmJ'];

        $str = '';

        ksort($params);

        foreach ($params as $k => $val) {
            $str .= $k . $val;
        }

        return hash_hmac('sha1', $str . 'j7uSz7ipmJ', 'EBjGihfGnxF');
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
