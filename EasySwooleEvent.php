<?php

namespace EasySwoole\EasySwoole;

use App\Crontab\Service\CrontabService;
use App\Event\EventList\TestEvent;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\RequestUtils\LimitService;
use App\Process\Service\ProcessService;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use wanghanwanghan\someUtils\control;

class EasySwooleEvent implements Event
{
    public static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');

        //注册全局事件
        TestEvent::getInstance()->set('testEvent', function () {
            echo control::getUuid() . PHP_EOL;
        });
    }

    public static function mainServerCreate(EventRegister $register)
    {
        //常量
        CreateDefine::getInstance()->createDefine(__DIR__);

        //mysql pool
        CreateMysqlPoolForProjectDb::getInstance()->createMysql();

        //mysql orm
        CreateMysqlOrm::getInstance()->createMysqlOrm();

        //redis pool
        CreateRedisPool::getInstance()->createRedis();

        //假装令牌桶
        LimitService::getInstance()->create();

        //注册自定义进程
        ProcessService::getInstance()->create();

        //注册定时任务
        CrontabService::getInstance()->create();
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {

    }
}