<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HuiCheJian\HuiCheJianService;
use App\HttpController\Service\MaYi\MaYiService;
use Carbon\Carbon;
use EasySwoole\EasySwoole\Crontab\AbstractCronTask;

class GetAuthBook extends AbstractCronTask
{
    private $crontabBase;

    //每次执行任务都会执行构造函数
    function __construct()
    {
        $this->crontabBase = new CrontabBase();
    }

    static function getRule(): string
    {
        //每5分钟执行一次
        return '*/5 * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        CommonService::getInstance()
            ->log4PHP(Carbon::now()->format('Y-m-d H:i:s'), 'GetAuthBookCrontabRunAt', 'ant.log');

        //准备获取授权书的企业列表
        $list = AntAuthList::create()->where([
            'authDate' => 0,
            'status' => MaYiService::STATUS_0,
        ])->all();

        if (!empty($list)) {

            foreach ($list as $oneEntInfo) {

                $data = [
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                    'legalPerson' => $oneEntInfo->getAttr('legalPerson'),
                    'idCard' => $oneEntInfo->getAttr('idCard'),
                    'phone' => $oneEntInfo->getAttr('phone'),
                    'region' => $oneEntInfo->getAttr('city'),
                    'address' => $oneEntInfo->getAttr('regAddress'),
                    'requestId' => $oneEntInfo->getAttr('requestId') . time(),//海光用的，没啥用，随便传
                ];

                $res = (new HuiCheJianService())
                    ->setCheckRespFlag(true)->getAuthPdf($data);

                if ($res['code'] !== 200) {
                    CommonService::getInstance()->log4PHP($data, 'GetAuthBookCrontabData', 'ant.log');
                    CommonService::getInstance()->log4PHP($res['msg'] ?? '', 'GetAuthBookCrontabMsg', 'ant.log');
                    continue;
                }

                $url = $res['result']['url'];

                $path = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
                is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0755);
                $filename = $oneEntInfo->getAttr('socialCredit') . '.pdf';

                //储存pdf
                file_put_contents(INV_AUTH_PATH . $path . $filename, file_get_contents($url), FILE_APPEND | LOCK_EX);

                //更新数据库
                AntAuthList::create()->where([
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                ])->update([
                    'filePath' => $path . $filename,
                    'authDate' => time(),
                    'status' => MaYiService::STATUS_1
                ]);

            }

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'GetAuthBookCrontabException', 'ant.log');
    }


}
