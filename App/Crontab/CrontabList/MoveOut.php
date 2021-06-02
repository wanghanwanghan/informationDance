<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\HttpController\Service\Zip\ZipService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use wanghanwanghan\someUtils\control;

class MoveOut extends AbstractCronTask
{
    private $crontabBase;

    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每天的凌晨5点
        return '0 5 * * *';
        //return '*/2 * * * *';
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

        if (!$this->crontabBase->withoutOverlapping(self::getTaskName())) {
            CommonService::getInstance()->log4PHP('不开始');
            return true;
        }

        $target_time = Carbon::now()->subDays(1)->format('Ymd');

        $sendHeaders['authorization'] = $this->createToken();

        $data = [
            'usercode' => 'j7uSz7ipmJ'
        ];

        $url = 'http://39.106.95.155/data/daily_ent_mrxd/?_t=' . time();

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

        return true;
    }

    function handleFileArr($filename_arr)
    {
        //inv_20210601215801 股东变更
        //inv_new_20210601215801 新股东

        //basic_20210601215801 基本信息变更
        //basic_new_20210601215801 新企业
    }

    //删除n天前创建的文件
    function delFileByCtime($dir, $n = 10): bool
    {
        if (strpos($dir, 'informationDance') === false) return true;

        if (is_dir($dir) && is_numeric($n)) {
            if ($dh = opendir($dir)) {
                while (false !== ($file = readdir($dh))) {
                    if ($file !== '.' && $file !== '..' && $file !== '.gitignore') {
                        $fullpath = $dir . $file;
                        if (is_dir($fullpath)) {
                            if (count(scandir($fullpath)) == 2) {
                                //rmdir($fullpath);
                                CommonService::getInstance()->log4PHP("rmdir {$fullpath}");
                            } else {
                                $this->delFileByCtime($fullpath, $n);
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

    }


}
