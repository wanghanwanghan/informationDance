<?php

namespace App\HttpController\Service;

use EasySwoole\Component\Singleton;
use EasySwoole\ORM\Db\Config;
use EasySwoole\ORM\Db\Connection;
use EasySwoole\ORM\DbManager;

class CreateMysqlOrm extends ServiceBase
{
    use Singleton;

    function createMysqlOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHost'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPort'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUser'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPassword'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabase'));
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabase'));

        return true;
    }

    function createEntDbOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHost'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPort'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUser'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPassword'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb'));
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb'));

        return true;
    }

    function createRDS3Orm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3'));
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3')
        );

        return true;
    }

    function createRDS3NicCodeOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code'));
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_nic_code')
        );

        return true;
    }

    function createRDS3SiJiFenLeiOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_si_ji_fen_lei'));
        $config->setCharset('utf8mb4');

        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_si_ji_fen_lei')
        );

        return true;
    }

    function createRDS3Prism1Orm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1'));
        $config->setCharset('utf8mb4');

        
        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_prism1')
        );

        return true;
    }

    function createRDS3ShangShiGongSiOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_shang_shi_gong_si'));
        $config->setCharset('utf8mb4');

        
        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_shang_shi_gong_si')
        );

        return true;
    }

    function createRDS3AllKuOrm(): bool
    {
        $config = new Config();

        //数据库配置
        $config->setHost(CreateConf::getInstance()->getConf('env.mysqlHostRDS_3'));
        $config->setPort(CreateConf::getInstance()->getConf('env.mysqlPortRDS_3'));
        $config->setUser(CreateConf::getInstance()->getConf('env.mysqlUserRDS_3'));
        $config->setPassword(CreateConf::getInstance()->getConf('env.mysqlPasswordRDS_3'));
        $config->setDatabase(CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_all_ku'));
        $config->setCharset('utf8mb4');

        
        //链接池配置
        $config->setGetObjectTimeout(3.0); //设置获取连接池对象超时时间
        $config->setIntervalCheckTime(30 * 1000); //设置检测连接存活执行回收和创建的周期
        $config->setMaxIdleTime(15); //连接池对象最大闲置时间(秒)
        $config->setMaxObjectNum(50); //设置最大连接池存在连接对象数量
        $config->setMinObjectNum(5); //设置最小连接池存在连接对象数量
        $config->setAutoPing(5); //设置自动ping客户端链接的间隔

        DbManager::getInstance()->addConnection(
            new Connection($config), CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_all_ku')
        );

        return true;
    }
}
