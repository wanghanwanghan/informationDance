<?php

namespace App\HttpController\Service\CreateTable;

use App\HttpController\Service\CreateConf;
use EasySwoole\Component\Singleton;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;

class CreateTableService
{
    use Singleton;

    //用户表
    function information_dance_user()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('username', 20)->setDefaultValue('');
            $table->colVarChar('password', 20)->setDefaultValue('');
            $table->colVarChar('phone', 20)->setDefaultValue('');
            $table->colVarChar('email', 100)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index', 'phone');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //统计表
    function information_dance_statistics()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('path', 255)->setDefaultValue('');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //计费表
    function information_dance_charge()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('计费表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colTinyInt('moduleId', 3)->setIsUnsigned()->setDefaultValue(0);
            $table->colVarChar('moduleName', 50)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colVarChar('detailKey', 100)->setDefaultValue('');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colDecimal('price', 10, 2)->setIsUnsigned()->setDefaultValue(0.00);
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index', 'phone');
            $table->indexNormal('phone_entName_detailKey_moduleId_index', ['phone', 'entName', 'detailKey', 'moduleId']);
            $table->indexNormal('created_at_index', 'created_at');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //用户钱包表
    function information_dance_wallet()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('用户钱包表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colDecimal('money', 10, 2)->setIsUnsigned()->setDefaultValue(0.00);
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index', 'phone');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //商品列表
    function information_dance_purchase_list()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('商品列表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('name', 10)->setDefaultValue('');
            $table->colVarChar('desc', 50)->setDefaultValue('');
            $table->colDecimal('money', 10, 2)->setIsUnsigned()->setDefaultValue(0.00);
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //商品购买记录表
    function information_dance_purchase_info()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('商品购买记录表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('uid', 11)->setIsUnsigned()->setColumnComment('用户主键');
            $table->colBigInt('orderId', 20)->setIsUnsigned();
            $table->colVarChar('orderStatus', 20)->setDefaultValue('待支付')->setColumnComment('异常，待支付，已支付，已关闭');
            $table->colInt('purchaseType', 11)->setIsUnsigned()->setColumnComment('purchase_list表中的主键');
            $table->colDecimal('payMoney', 10, 2)->setIsUnsigned()->setColumnComment('花了多少钱');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('orderId', 'orderId');
            $table->indexNormal('updated_at', 'updated_at');
            $table->indexNormal('uid_purchaseType', ['uid', 'purchaseType']);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //留言一句话
    function information_dance_one_said()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('留言一句话')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->tinyint('moduleId', 3)->setIsUnsigned();
            $table->colVarChar('oneSaid', 255)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_index', 'phone');
            $table->indexNormal('phone_entName_moduleId_index', ['phone', 'entName', 'moduleId']);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //风险监控 用户-企业-过期时间 关联表
    function information_dance_supervisor_uid_entName()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('风险监控用户企业关联表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setColumnComment('1正在监控，2未监控，3已过期');
            $table->colInt('expireTime', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //风险监控 企业风险表
    function information_dance_supervisor_entName_info()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('企业风险表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colTinyInt('type', 3)->setIsUnsigned()->setColumnComment('风险类型大分类');
            $table->colTinyInt('typeDetail', 3)->setIsUnsigned()->setColumnComment('风险类型小分类');
            $table->colInt('timeRange', 11)->setIsUnsigned()->setColumnComment('时间');
            $table->colTinyInt('level', 3)->setIsUnsigned()->setColumnComment('风险等级');
            $table->colVarChar('desc', 200)->setColumnComment('风险说明');
            $table->colText('content')->setColumnComment('风险内容');
            $table->colVarChar('detailUrl', 200)->setColumnComment('详情链接');
            $table->colVarChar('keyNo', 200)->setColumnComment('详情唯一主键');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }


}
