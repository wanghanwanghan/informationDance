<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\FinancesSearch;
use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\LongDun\LongDunService;
use App\HttpController\Service\LongXin\LongXinService;
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
            $this->h_caiwu();
            $this->h_lianjie();
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

                if ($res['code'] === 200 && !empty($res['result'])) {

                    $fengxianDetail = [];

                    if (!empty($res['result']['Data']) && is_array($res['result']['Data'])) {

                        foreach ($res['result']['Data'] as $oneFX) {

                            $model = [
                                'title' => '经营异常',
                                'desc' => $oneFX['AddReason'],
                                'content' => '',
                                'date' => $oneFX['AddDate'],
                                'remarks' => $oneFX['DecisionOffice'],
                                'reservedFields' => '',
                            ];

                            $fengxianDetail[] = $model;

                        }

                    }

                    $one->update([
                        'fengxian' => $res['result']['VerifyResult'] - 0,
                        'fengxianDetail' => empty($fengxianDetail) ? '' : jsonEncode($fengxianDetail, false)
                    ]);

                } else {

                    $one->update([
                        'fengxian' => '处理失败'
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

            foreach ($list as $one) {

                $postData = [
                    'entName' => $one->entName,
                    'code' => '',
                    'beginYear' => 2020,
                    'dataCount' => 1,
                ];

                $res = (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getFinanceData($postData, false);

                if ($res['code'] == 200) {

                    ksort($res['result']);

                    $tmp = current($res['result']);

                    $one->update([
                        'caiwu' => is_numeric($tmp['VENDINC']) ? $tmp['VENDINC'] : '无数据',
                        'caiwuDetail' => is_string($tmp) ? $tmp : jsonEncode($tmp, false),
                    ]);

                } else {

                    $one->update([
                        'caiwu' => '处理失败'
                    ]);

                }


            }

        }
    }

    private function h_lianjie(): void
    {
        $list = FinancesSearch::create()->where([
            'lianjie' => '等待处理',
            'is_show' => 1,
        ])->page(1)->all();

        if (!empty($list)) {

            foreach ($list as $one) {

                $post_data = [
                    'entName' => $one->entName
                ];

                $res = (new LongXinService())
                    ->setCheckRespFlag(true)
                    ->getEntLianXi($post_data);

                if ($res['code'] == 200) {

                    $one->update([
                        'lianjie' => count($res['result']),
                        'lianjieDetail' => is_string($res['result']) ?
                            $res['result'] :
                            jsonEncode($res['result'], false),
                    ]);

                } else {

                    $one->update([
                        'lianjie' => '处理失败'
                    ]);

                }

            }

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
