<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Manager;

class CreateMysqlPoolForEntDb extends AbstractPool
{
    use Singleton;

    protected $mysqlConf;

    function __construct()
    {
        parent::__construct(new \EasySwoole\Pool\Config());

        $mysqlConf = new Config([
            'host' => CreateConf::getInstance()->getConf('env.mysqlHost'),
            'port' => CreateConf::getInstance()->getConf('env.mysqlPort'),
            'user' => CreateConf::getInstance()->getConf('env.mysqlUser'),
            'password' => CreateConf::getInstance()->getConf('env.mysqlPassword'),
            'database' => CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb'),
            'timeout' => 5,
            'charset' => 'utf8mb4',
        ]);

        $this->mysqlConf = $mysqlConf;
    }

    protected function createObject()
    {
        return new Client($this->mysqlConf);
    }

    //注册连接池，只能在mainServerCreate中用
    function createMysql()
    {
        Manager::getInstance()->register(CreateMysqlPoolForEntDb::getInstance(), CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb'));

        return true;
    }
}
