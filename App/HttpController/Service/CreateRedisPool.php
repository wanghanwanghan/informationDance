<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Redis\Config\RedisConfig;
use EasySwoole\RedisPool\Redis;

class CreateRedisPool extends ServiceBase
{
    use Singleton;

    //注册redis连接池，只能在mainServerCreate中用
    public function createRedis()
    {
        $conf = new RedisConfig();
//        $conf->setHost(CreateConf::getInstance()->getConf('env.redisHost'));
//        $conf->setPort(CreateConf::getInstance()->getConf('env.redisPort'));
        $conf->setHost('39.105.35.154');
        $conf->setPort(56379);
        $conf->setTimeout(5);
        $conf->setAuth(CreateConf::getInstance()->getConf('env.redisPassword'));
        $conf->setSerialize(RedisConfig::SERIALIZE_NONE);

        $redisPoolConfig = Redis::getInstance()->register('redis', $conf);
        $redisPoolConfig->setMinObjectNum(10);
        $redisPoolConfig->setMaxObjectNum(200);
        $redisPoolConfig->setAutoPing(10);

        return true;
    }
}
