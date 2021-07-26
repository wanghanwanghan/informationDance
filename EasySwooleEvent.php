<?php

namespace EasySwoole\EasySwoole;

use App\Crontab\Service\CrontabService;
use App\Event\EventList\TestEvent;
use App\HttpController\Service\CreateConf;
use App\HttpController\Service\CreateDefine;
use App\HttpController\Service\CreateMysqlOrm;
use App\HttpController\Service\CreateMysqlPoolForEntDb;
use App\HttpController\Service\CreateMysqlPoolForMinZuJiDiDb;
use App\HttpController\Service\CreateMysqlPoolForProjectDb;
use App\HttpController\Service\CreateRedisPool;
use App\HttpController\Service\CreateSessionHandler;
use App\HttpController\Service\RequestUtils\LimitService;
use App\Process\ProcessList\ConsumeOcrProcess;
use App\Process\ProcessList\Docx2Doc;
use App\Process\Service\ProcessService;
use App\SwooleTable\Service\SwooleTableService;
use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Message\Status;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
use wanghanwanghan\someUtils\control;

class EasySwooleEvent implements Event
{
    static function initialize()
    {
        date_default_timezone_set('Asia/Shanghai');

        //注册全局事件
        TestEvent::getInstance()->set('testEvent', function () {
            echo control::getUuid() . PHP_EOL;
        });
    }

    static function mainServerCreate(EventRegister $register)
    {
        //常量
        CreateDefine::getInstance()->createDefine(__DIR__);

        //加载yaconf
        CreateConf::getInstance()->create(__DIR__);

        //mysql pool
        CreateMysqlPoolForProjectDb::getInstance()->createMysql();
        CreateMysqlPoolForEntDb::getInstance()->createMysql();
        CreateMysqlPoolForMinZuJiDiDb::getInstance()->createMysql();

        //mysql orm
        CreateMysqlOrm::getInstance()->createMysqlOrm();
        CreateMysqlOrm::getInstance()->createEntDbOrm();

        //redis pool
        CreateRedisPool::getInstance()->createRedis();

        //假装令牌桶
        LimitService::getInstance()->create();

        //注册自定义进程
        //ProcessService::getInstance()->create(Docx2Doc::class, 'docx2doc');
        //ProcessService::getInstance()->create(ConsumeOcrProcess::class, 'consumeOcr');

        //注册定时任务
        CrontabService::getInstance()->create();

        //注册session的处理流程
        CreateSessionHandler::getInstance()->create(SESSION_PATH);

        //swoole table service
        SwooleTableService::getInstance()->create();
    }

    static function onRequest(Request $request, Response $response): bool
    {
        $response->withHeader('Access-Control-Allow-Origin', '*');
        $response->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->withHeader('Access-Control-Allow-Credentials', 'true');
        $response->withHeader('Access-Control-Allow-Headers', '*');

        if ($request->getMethod() === 'OPTIONS') {
            $response->withStatus(Status::CODE_OK);
            return false;
        }

        return true;
    }

    static function afterRequest(Request $request, Response $response): void
    {
        $time = time();
        $phone = $request->getRequestParam('phone') ?? null;
        $realIp = null;
        if (isset($request->getHeader('x-real-ip')[0])) {
            $realIp = $request->getHeader('x-real-ip')[0];
        }
    }
}