<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Manager;

class CreateMysqlPoolForRDS3Prism1 extends AbstractPool
{
    use Singleton;

    protected $mysqlConf;

    function __construct()
    {
        parent::__construct(new \EasySwoole\Pool\Config());

        $mysqlConf = new Config([
            'host' => CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'),
            'port' => CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'),
            'user' => CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'),
            'password' => CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'),
            'database' => CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'),
            'timeout' => 5,
            'charset' => 'utf8mb4',
        ]);

        $this->mysqlConf = $mysqlConf;
    }

    protected function createObject(): Client
    {
        return new Client($this->mysqlConf);
    }

    //注册连接池，只能在mainServerCreate中用
    function createMysql(): Manager
    {
        return Manager::getInstance()
            ->register(
                CreateMysqlPoolForEntDb::getInstance(),
                CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1')
            );
            
    }
}
