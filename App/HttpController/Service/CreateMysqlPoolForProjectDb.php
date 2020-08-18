<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\Mysqli\Client;
use EasySwoole\Mysqli\Config;
use EasySwoole\Pool\AbstractPool;
use EasySwoole\Pool\Manager;

class CreateMysqlPoolForProjectDb extends AbstractPool
{
    use Singleton;

    protected $mysqlConf;

    public function __construct()
    {
        parent::__construct(new \EasySwoole\Pool\Config());

        $mysqlConf = new Config([
            'host'     => \Yaconf::get('env.mysqlHost'),
            'port'     => \Yaconf::get('env.mysqlPort'),
            'user'     => \Yaconf::get('env.mysqlUser'),
            'password' => \Yaconf::get('env.mysqlPassword'),
            'database' => \Yaconf::get('env.mysqlDatabase'),
            'timeout'  => 5,
            'charset'  => 'utf8mb4',
        ]);

        $this->mysqlConf = $mysqlConf;
    }

    protected function createObject()
    {
        return new Client($this->mysqlConf);
    }

    //注册连接池，只能在mainServerCreate中用
    public function createMysql()
    {
        Manager::getInstance()->register(CreateMysqlPoolForProjectDb::getInstance(),\Yaconf::get('env.mysqlDatabase'));

        return true;
    }
}
