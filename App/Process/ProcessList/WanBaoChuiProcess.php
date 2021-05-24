<?php

namespace App\Process\ProcessList;

use App\HttpController\Service\Common\CommonService;
use App\HttpController\Service\HttpClient\CoHttpClient;
use App\Process\ProcessBase;
use Carbon\Carbon;
use Swoole\Process;
use Swoole\Coroutine;

class WanBaoChuiProcess extends ProcessBase
{
    public $token1 = 'df0b8ce22bb092a98a533f66705f50b7';
    public $token2 = '552e9f6b0746026136cd7bca034812ce';

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        while (true) {
            $now = Carbon::now()->format('Hi') - 0;
            if (in_array($now, [930, 931, 1330, 1331, 1930, 1931], true)) {
                $res = (new CoHttpClient())->useCache(false)
                    ->send('http://wbcapi.shuhuiguoyou.com/auction/0/?page=10', [], [
                        'token' => $this->token1,
                    ], [], 'get');
                $target = [];
                foreach ($res['data']['results'] as $one) {
                    if (!is_numeric($one['id'])) continue;
                    $target[] = $one['id'] - 0;
                }
                foreach ($target as $one_id) {
                    Coroutine::create(function () use ($one_id) {
                        (new CoHttpClient())
                            ->useCache(false)
                            ->send("http://wbcapi.shuhuiguoyou.com/auctions/{$one_id}/", [], [
                                'token' => $this->token1,
                            ]);
                    });
                    Coroutine::create(function () use ($one_id) {
                        (new CoHttpClient())
                            ->useCache(false)
                            ->send("http://wbcapi.shuhuiguoyou.com/auctions/{$one_id}/", [], [
                                'token' => $this->token2,
                            ]);
                    });
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
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();

        $content = "[file => {$file}] [line => {$line}] [msg => {$msg}]";

        CommonService::getInstance()->log4PHP($content, 'info', 'wanbaocui.log');
    }


}
