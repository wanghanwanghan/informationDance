<?php

namespace App\Crontab;

use EasySwoole\RedisPool\Redis;

class CrontabBase
{
    private $redis_db_num = 14;

    function setRedisDbNum(int $num): CrontabBase
    {
        $this->redis_db_num = $num;
        return $this;
    }

    function withoutOverlapping($className, $ttl = 86400): bool
    {
        //返回true是可以执行，返回false是不能执行
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select($this->redis_db_num);

        $status = !!$redis->setNx($name, 'isRun');

        $status === false ?: $redis->expire($name, $ttl);

        return $status;
    }

    static function withoutOverlappingV2($className, $ttl = 86400)
    {
        //返回true是可以执行，返回false是不能执行
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        $status = (bool)$redis->setNx($name, 'isRun');

        $status === false ?: $redis->expire($name, $ttl);

        return $status;
    }

    function removeOverlappingKey($className): bool
    {
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select($this->redis_db_num);

        return !!$redis->del($name);
    }

    static function removeOverlappingKeyV2($className): bool
    {
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        return !!$redis->del($name);
    }


}
