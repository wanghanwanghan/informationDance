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
    public $token1 = null;
    public $token2 = null;
    public $is_login = false;
    public $target = [];

    protected function run($arg)
    {
        //可以用来初始化
        parent::run($arg);

        while (true) {
            $now = Carbon::now()->format('Hi') - 0;
            if (in_array($now, [930, 1000, 1330, 1930], true)) {
                if (!$this->is_login) {
                    $this->getLogin();
                    $this->getAuctionsList();
                }
                $this->doAuctions($this->target);
            } else {
                $this->is_login = false;
            }
        }
    }

    protected function doAuctions($target)
    {
        foreach ($target as $one_id) {
            Coroutine::create(function () use ($one_id) {
                $res = (new CoHttpClient())
                    ->useCache(false)
                    ->needJsonDecode(true)
                    ->send("http://wbcapi.shuhuiguoyou.com/auctions/{$one_id}/", [], [
                        'token' => $this->token1,
                    ]);

                CommonService::getInstance()->log4PHP([
                    '开始拍' => ['id' => $one_id, 'res' => $res]
                ]);
            });
            Coroutine::create(function () use ($one_id) {
                (new CoHttpClient())
                    ->useCache(false)
                    ->needJsonDecode(true)
                    ->send("http://wbcapi.shuhuiguoyou.com/auctions/{$one_id}/", [], [
                        'token' => $this->token2,
                    ]);
            });
        }
    }

    protected function getAuctionsList()
    {
        $target = [];

        try {
            foreach ([4, 6, 8] as $one) {
                $res = (new CoHttpClient())->useCache(false)->needJsonDecode(true)
                    ->send("http://wbcapi.shuhuiguoyou.com/auction/0/?page={$one}", [], [
                        'token' => $this->token1,
                    ], [], 'get');

                CommonService::getInstance()->log4PHP([
                    '取得列表' => $res
                ]);

                if (is_array($res) && !empty($res['data']['results'])) {
                    foreach ($res['data']['results'] as $two) {
                        if (!is_numeric($two['id'])) continue;
                        $target[] = $two['id'] - 0;
                    }
                }
            }
        } catch (\Throwable $e) {
            $this->recodeErr($e, __FUNCTION__);
        }

        $this->target = $target;

        CommonService::getInstance()->log4PHP([
            '列表是' => $this->target
        ]);
    }

    protected function getLogin()
    {
        try {
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(true)
                ->send('http://wbcapi.shuhuiguoyou.com/login/', [
                    'mobile' => '13968525505',
                    'password' => 'cll912922',
                ]);

            CommonService::getInstance()->log4PHP([
                '取得token1' => $res
            ]);

            $this->token1 = $res['data']['token'];
        } catch (\Throwable $e) {
            $this->recodeErr($e, __FUNCTION__);
        }

        try {
            $res = (new CoHttpClient())->useCache(false)->needJsonDecode(true)
                ->send('http://wbcapi.shuhuiguoyou.com/login/', [
                    'mobile' => '13376863377',
                    'password' => 'cll912922',
                ]);

            CommonService::getInstance()->log4PHP([
                '取得token2' => $res
            ]);

            $this->token2 = $res['data']['token'];
        } catch (\Throwable $e) {
            $this->recodeErr($e, __FUNCTION__);
        }

        $this->is_login = true;
    }

    protected function recodeErr(\Throwable $throwable, $func_name): void
    {
        $file = $throwable->getFile();
        $line = $throwable->getLine();
        $msg = $throwable->getMessage();

        $content = "[file => {$file}] [func => {$func_name}] [line => {$line}] [msg => {$msg}]";

        CommonService::getInstance()->log4PHP($content, 'info', 'wanbaocui.log');
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
