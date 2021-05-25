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

        while (false) {
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

    protected function getAuctionsList()
    {
        $target = [];

        foreach ([4, 6, 8] as $one) {
            $res = (new CoHttpClient())->useCache(false)
                ->send("http://wbcapi.shuhuiguoyou.com/auction/0/?page={$one}", [], [
                    'token' => $this->token1,
                ], [], 'get');
            if (is_string($res)) $res = jsonDecode($res);
            foreach ($res['data']['results'] as $two) {
                if (!is_numeric($two['id'])) continue;
                $target[] = $two['id'] - 0;
            }
        }

        CommonService::getInstance()->log4PHP([
            'targetList' => $target
        ]);

        $this->target = $target;
    }

    protected function doAuctions($target)
    {
        foreach ($target as $one_id) {
            Coroutine::create(function () use ($one_id) {
                $res = (new CoHttpClient())
                    ->useCache(false)
                    ->send("http://wbcapi.shuhuiguoyou.com/auctions/{$one_id}/", [], [
                        'token' => $this->token1,
                    ]);
                if (is_string($res)) $res = jsonDecode($res);
                CommonService::getInstance()->log4PHP([
                    'doAuctions' => $res
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

    protected function getLogin()
    {
        $res = (new CoHttpClient())->useCache(false)
            ->send('http://wbcapi.shuhuiguoyou.com/login/', [
                'mobile' => '13968525505',
                'password' => 'cll912922',
            ]);

        $res = jsonDecode($res);

        $this->token1 = $res['data']['token'];

        CommonService::getInstance()->log4PHP([
            'token1登陆' => $res
        ]);

        $res = (new CoHttpClient())->useCache(false)
            ->send('http://wbcapi.shuhuiguoyou.com/login/', [
                'mobile' => '13376863377',
                'password' => 'cll912922',
            ]);

        $res = jsonDecode($res);

        $this->token2 = $res['data']['token'];

        $this->is_login = true;
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
