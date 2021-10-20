<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\Process\ProcessBase;
use Swoole\Process;

class FinancesSearchHandleFengXianAndCaiWu extends ProcessBase
{
    public $ldUrl;

    protected function run($arg)
    {
        $this->ldUrl = CreateConf::getInstance()->getConf('longdun.baseUrl');

        //可以用来初始化
        parent::run($arg);

        while (true) {
            $this->h_fengxian();
            break;
            $this->h_caiwu();
            \co::sleep(5);
        }
    }

    private function h_fengxian(): void
    {
        $list = FinancesSearch::create()->where([
            'fengxian' => '等待处理',
            'is_show' => 1,
        ])->page(1)->all();

        if (!empty($list)) {

            foreach ($list as $one) {

                $postData = [
                    'searchKey' => $one->entName,
                ];

                $res = (new LongDunService())->setCheckRespFlag(true)
                    ->get($this->ldUrl . 'ExceptionCheck/GetList', $postData);

                CommonService::getInstance()->log4PHP($res);

                if ($res['code'] == 200 && !empty($res['result'])) {

                    $one->update([
                        'fengxian' => $res['result']['VerifyResult'] - 0
                    ]);

                }

            }

        }
    }

    private function h_caiwu(): void
    {
        $list = FinancesSearch::create()->where([
            'caiwu' => '等待处理',
            'is_show' => 1,
        ])->page(1)->all();

        if (!empty($list)) {

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
    }


}
