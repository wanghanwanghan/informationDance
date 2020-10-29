<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Manager;

class CreateMysqlPoolForMinZuJiDiDb extends AbstractPool
{
    use Singleton;

    protected $mysqlConf;

    function __construct()
    {
        parent::__construct(new \EasySwoole\Pool\Config());

        $mysqlConf = new Config([
            'host' => CreateConf::getInstance()->getConf('env.mysqlHostMZJD'),
            'port' => CreateConf::getInstance()->getConf('env.mysqlPortMZJD'),
            'user' => CreateConf::getInstance()->getConf('env.mysqlUserMZJD'),
            'password' => CreateConf::getInstance()->getConf('env.mysqlPasswordMZJD'),
            'database' => CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'),
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
        Manager::getInstance()->register(CreateMysqlPoolForMinZuJiDiDb::getInstance(), CreateConf::getInstance()->getConf('env.mysqlDatabaseMZJD'));

        return true;
    }
}
