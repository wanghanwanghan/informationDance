<?php

namespace App\Crontab\CrontabList;

use App\Crontab\CrontabBase;
use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\FaDaDa\FaDaDaService;
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
        //每分钟执行一次
        return '* * * * *';
    }

    static function getTaskName(): string
    {
        return __CLASS__;
    }

    function run(int $taskId, int $workerIndex)
    {
        //准备获取授权书的企业列表
        $list = AntAuthList::create()->where([
            'authDate' => 0,
            'status' => MaYiService::STATUS_0,
        ])->all();

        if (!empty($list)) {

            foreach ($list as $oneEntInfo) {

                $data = [
                    'entName' => $oneEntInfo->getAttr('entName'),// entName companyname
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),//taxno  newtaxno
                    'legalPerson' => $oneEntInfo->getAttr('legalPerson'),//signName
                    'idCard' => $oneEntInfo->getAttr('idCard'),
                    'phone' => $oneEntInfo->getAttr('phone'),//phoneno
                    'city' => $oneEntInfo->getAttr('city'),//region
                    'regAddress' => $oneEntInfo->getAttr('regAddress'),//address
                    'requestId' => $oneEntInfo->getAttr('requestId') . time(),//海光用的，没啥用，随便传
                ];

                $res = (new FaDaDaService())->setCheckRespFlag(true)->getAuthFile($data);
                CommonService::getInstance()->log4PHP($res,'info','get_auth_file_return_res');
//                $res = (new HuiCheJianService())
//                    ->setCheckRespFlag(true)->getAuthPdf($data);

                if ($res['code'] !== 200) {
                    continue;
                }

                $url = $res['result']['url'];

                //更新数据库
                AntAuthList::create()->where([
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                ])->update([
                    'filePath' => $url,
                    'authDate' => time(),
                    'status' => MaYiService::STATUS_1
                ]);

            }

        }
    }

    function onException(\Throwable $throwable, int $taskId, int $workerIndex)
    {

    }


}
