<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\AntAuthList;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HuiCheJian\HuiCheJianService;
use App\HttpController\Service\MaYi\MaYiService;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;

class GetAuthBookProcess extends ProcessBase
{
    protected function run($arg)
    {
        //没发送的授权书，每月固定时间给大象发过去
        while (true) {

            //准备获取授权书的企业列表
            $list = AntAuthList::create()->where([
                'authDate' => 0,
                'status' => MaYiService::STATUS_0,
            ])->all();

            if (empty($list)) {
                continue;
            }

            foreach ($list as $oneEntInfo) {

                $data = [
                    'entName' => $oneEntInfo->getAttr('entName'),
                    'socialCredit' => $oneEntInfo->getAttr('socialCredit'),
                    'legalPerson' => $oneEntInfo->getAttr('legalPerson'),
                    'idCard' => $oneEntInfo->getAttr('idCard'),
                    'phone' => $oneEntInfo->getAttr('phone'),
                    'region' => $oneEntInfo->getAttr('city'),
                    'address' => $oneEntInfo->getAttr('regAddress'),
                    'requestId' => $oneEntInfo->getAttr('requestId'),
                ];

                $res = (new HuiCheJianService())
                    ->setCheckRespFlag(true)->getAuthPdf($data);

                if ($res['code'] !== 200) {
                    CommonService::getInstance()->log4PHP($data, 'GetAuthBookProcessData', 'ant.log');
                    CommonService::getInstance()->log4PHP($res['msg'] ?? '', 'GetAuthBookProcessMsg', 'ant.log');
                    continue;
                }

                $res = current($res['result']);
                $url = $res['url'];

                $path = Carbon::now()->format('Ymd') . DIRECTORY_SEPARATOR;
                is_dir(INV_AUTH_PATH . $path) || mkdir(INV_AUTH_PATH . $path, 0644);
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

            \co::sleep(3600);

        }
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
        CommonService::getInstance()->log4PHP($throwable->getTraceAsString(), 'GetAuthBookProcessShutDown', 'ant.log');
    }


}
