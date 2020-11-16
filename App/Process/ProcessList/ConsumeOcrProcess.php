<?php

namespace App\Process\ProcessList;

use App\HttpController\Models\Api\OcrQueue;
use App\HttpController\Service\BaiDu\BaiDuService;
use App\HttpController\Service\Common\CommonService;
use App\Process\ProcessBase;
use EasySwoole\RedisPool\Redis;
use Swoole\Process;
use wanghanwanghan\someUtils\control;

class ConsumeOcrProcess extends ProcessBase
{
    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        //接收参数可以是字符串也可以是数组

        CommonService::getInstance()->log4PHP(__CLASS__ . ' 启动');

        $this->consume();
    }

    protected function consume()
    {
        //自定义进程不需要传参数，启动后就一直消费一个列队
        while (true) {
            try {
                $list = OcrQueue::create()->where('status', 0)->limit(0, 10)->all();
                $list = obj2Arr($list);
                if (empty($list)) {
                    \co::sleep(2);
                    continue;
                }
                foreach ($list as $one) {
                    $files = explode(',', $one['filename']);
                    is_array($files) ?: $files = [$files];
                    $content = '';
                    foreach ($files as $two) {
                        $res = BaiDuService::getInstance()->ocr(file_get_contents(OCR_PATH . $two));
                        (isset($res['words_result']) && !empty($res['words_result'])) ? $res = $res['words_result'] : $res = '';
                        if (!empty($res)) {
                            $res = control::array_flatten($res);
                            $res = implode(',', $res);
                        }
                        $content .= $res;
                    }
                    $info = OcrQueue::create()->where('reportNum', $one['reportNum'])
                        ->where('phone', $one['phone'])
                        ->where('catalogueNum', $one['catalogueNum'])->get();
                    $info->update(['status' => 1, 'content' => $content]);
                }
            } catch (\Throwable $e) {
                \co::sleep(60);
                CommonService::getInstance()->log4PHP(__CLASS__, $e->getMessage());
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
        CommonService::getInstance()->log4PHP(__CLASS__, $throwable->getMessage());
    }


}
