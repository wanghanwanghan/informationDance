<?php

namespace App\HttpController\Service\CreateTable;

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

        $obj = Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->recycleObj($obj);

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

        $obj = Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->recycleObj($obj);

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

        $obj = Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->recycleObj($obj);

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

        $obj = Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(\Yaconf::get('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

}
