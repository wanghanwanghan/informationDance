<?php

namespace EasySwoole\EasySwoole;

use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\RequestUtils\LimitService;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');
    }

    public static function mainServerCreate(EventRegister $register)
    {
        //常量
        CreateDefine::getInstance()->CreateDefine(__DIR__);

        //mysql pool
        CreateMysqlPoolForProjectDb::getInstance()->createMysql();

        //mysql orm
        CreateMysqlOrm::getInstance()->createMysqlOrm();

        //redis pool
        CreateRedisPool::getInstance()->createRedis();

        //假装令牌桶
        LimitService::getInstance()->create();
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {

    }
}