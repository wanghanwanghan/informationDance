<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
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
        //每7天的凌晨2点
        //return '0 2 */7 * *';
        return '* * * * *';
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

        $sendHeaders['authorization'] = $this->createToken();

        $data = [
            'usercode' => 'j7uSz7ipmJ'
        ];

        $url = 'http://39.106.95.155/data/daily_ent_mrxd/?_t=' . time();

        $res = (new CoHttpClient())->send($url, $data, $sendHeaders);

        if (is_string($res)) CommonService::getInstance()->log4PHP('is_string');
        if (is_array($res)) CommonService::getInstance()->log4PHP('is_array');

        CommonService::getInstance()->log4PHP($res);

        $this->crontabBase->removeOverlappingKey(self::getTaskName());

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
