<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\MaYi\MaYiService;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class GetInvData extends AbstractCronTask
{
    public $crontabBase;
    public $currentAesKey;
    public $redisKey = 'readyToGetInvData_';
    public $readToSendAntFlag = 'readyToGetInvData_readToSendAntFlag_';

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每月18号18点可以取上一个月全部数据
        //return '0 18 18 * *';
        return '*/15 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        $this->currentAesKey = control::getUuid(16);

        $redis = Redis::defer('redis');
        $redis->select(15);

        $redis->hset($this->readToSendAntFlag, 'current_aes_key', $this->currentAesKey);

        for ($i = 1; $i <= 999999; $i++) {
            $limit = 1000;
            $offset = ($i - 1) * $limit;
            $list = AntAuthList::create()
                ->where('status', MaYiService::STATUS_3)
                ->limit($offset, $limit)->all();
            if (empty($list)) {
                break;
            }
            //可以取数了
            foreach ($list as $one) {
                $id = $one->getAttr('id');
                $suffix = $id % \App\Process\ProcessList\GetInvData::ProcessNum;
                //放到redis队列
                $key = $this->redisKey . $suffix;
                $redis->lPush($key, jsonEncode($one, false));
                $redis->hset($this->readToSendAntFlag, $this->readToSendAntFlag . $suffix, 1);
            }
        }

        //判断 $this->readToSendAntFlag 里是不是都是0，0代表没有处理的任务
        while (true) {
            $flag_arr = [];
            $num = \App\Process\ProcessList\GetInvData::ProcessNum;
            for ($i = $num; $i--;) {
                $flag = $redis->hGet($this->readToSendAntFlag, $this->readToSendAntFlag . $num) - 0;
                $flag !== 0 ?: $flag_arr[] = $flag;
            }
            if (count($flag_arr) !== $num) {
                \co::sleep(3);
                continue;
            }
            $ret = $this->sendToAnt();
            break;
        }

        CommonService::getInstance()->log4PHP('通知蚂蚁完毕');
    }

    //通知蚂蚁
    function sendToAnt(): bool
    {
        //根据三个id，通知不同的url
        $url_arr = [
            36 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',
            41 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',
            42 => 'http://invoicecommercial.dev.dl.alipaydev.com/api/wezTech/collectNotify',
        ];

        $collectNotify = [
            'body' => [
                [
                    'nsrsbh' => 'string // 授权的企业税号',
                    'authTime' => 'string // 授权时间',
                    'authResultCode' => 'string // 取数结果状态码 0000取数成功 XXXX取数失败',
                    'companyName' => 'string // 公司名称',
                    'fileSecret' => 'string // 对称钥秘⽂',
                    'fileKeyList' => '[string] // ⽂件路径',
                ],
            ],
            'head' => [
                'sign' => 'string // 签名',
                'notifyChannel' => 'string // 通知 渠道'
            ],
        ];

        return true;
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }

}
