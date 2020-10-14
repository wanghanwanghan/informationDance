<?php

namespace App\Crontab;

use EasySwoole\RedisPool\Redis;

class CrontabBase
{
    function withoutOverlapping($className): bool
    {
        //返回true是可以执行，返回false是不能执行
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        return $redis->setNx($name, 'isRun') ? true : false;
    }

    function removeOverlappingKey($className)
    {
        $name = explode("\\", $className);

        $name = end($name);

        $redis = Redis::defer('redis');

        $redis->select(14);

        return $redis->del($name);
    }


}
