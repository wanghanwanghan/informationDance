<?php

namespace App\HttpController\Service\CreateTable;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;

class CreateTableService extends ServiceBase
{
    use Singleton;

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








}
