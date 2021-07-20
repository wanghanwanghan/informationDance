<?php

namespace App\HttpController\Service\RequestUtils;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;
use wanghanwanghan\someUtils\control;

class LimitService extends ServiceBase
{
    use Singleton;

    //每分钟最大请求次数
    private $maxNum;

    private $db;

    //mainServerCreate中调用
    function create(): bool
    {
        $this->maxNum = CreateConf::getInstance()->getConf('env.limitServiceMaxNum');
        $this->db = CreateConf::getInstance()->getConf('env.limitServiceCacheRedisDB');
        return true;
    }

    function check($token, $realIp): bool
    {
        //空就不检查了，只检查带token的请求
        if (empty($token) && empty($realIp)) return true;

        $minute = Carbon::now()->format('YmdHi');

        //key
        empty($token) ? $key = $realIp : $key = $token;

        return Redis::invoke('redis', function (\EasySwoole\Redis\Redis $redis) use ($minute, $key) {
            $redis->select($this->db);
            $data = $redis->get($minute . $key);
            if (empty($data)) {
                //说明这分钟还没有请求过
                $redis->setEx($minute . $key, $this->random(), $this->maxNum);
            } else {
                //判断剩余次数
                if ($data <= 0) return false;
                $redis->decr($minute . $key);
            }
            return true;
        });
    }

    //随机一个过期时间
    private function random(): int
    {
        return control::randNum(2) * 6;//两位数 10 - 99，过期时间最少60秒
    }
}
