<?php

namespace App\HttpController\Service\RequestUtils;

use App\HttpController\Service\ServiceBase;
use Carbon\Carbon;
use EasySwoole\Component\Singleton;
use EasySwoole\RedisPool\Redis;

class LimitService extends ServiceBase
{
    use Singleton;

    //每分钟最大请求次数
    private $maxNum;

    private $db;

    //mainServerCreate中调用
    function create()
    {
        $this->maxNum = \Yaconf::get('env.limitServiceMaxNum');
        $this->db = \Yaconf::get('env.limitServiceCacheRedisDB');
        return true;
    }

    function check($token): bool
    {
        //空就不检查了，只检查带token的请求
        if (empty($token)) return true;

        $minute = Carbon::now()->format('YmdHi');

        $redis = Redis::defer('redis');

        $redis->select(14);

        //取得结果
        $data = $redis->get($minute . $token);

        if (empty($data)) {
            //说明这分钟还没有请求过
            $redis->setEx($minute . $token, $this->random(), $this->maxNum);

        } else {
            //判断剩余次数
            if ($data <= 0) return false;
            $redis->decr($minute . $token);
        }

        return true;
    }

    //随机一个 2-5 分钟的过期时间
    private function random()
    {
        mt_srand();
        $data = mt_rand(2, 5);
        return $data * 60;
    }
}
