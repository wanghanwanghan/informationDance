<?php

namespace App\HttpController\Service\XinDong;

use App\HttpController\Service\CreateConf;
use App\HttpController\Service\ServiceBase;
use EasySwoole\DDL\Blueprint\Table;
use EasySwoole\DDL\DDLBuilder;
use EasySwoole\DDL\Enum\Character;
use EasySwoole\DDL\Enum\Engine;
use EasySwoole\Pool\Manager;

class XinDongKeDongService extends ServiceBase
{
    function __construct()
    {
        return parent::__construct();
    }

    private function checkResp($code, $paging, $result, $msg): array
    {
        return $this->createReturn((int)$code, $paging, $result, $msg);
    }

    //根据用户uid分到20个表里
    function createTable(int $suffix): bool
    {
        $name = CreateConf::getInstance()->getConf('env.mysqlDatabase');
        $sql = DDLBuilder::table('approximateenterprise_' . $suffix, function (Table $table) {
            $table->setTableComment('根据用户画像跑出来的企业名单')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8_BIN);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('userid', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户id');
            $table->colInt('companyid', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('h库公司id');
            $table->colVarChar('esid', 50)->setDefaultValue('')->setColumnComment('es文档id');
            $table->colVarChar('code', 50)->setDefaultValue('')->setColumnComment('统一代码');
            $table->colDecimal('score', 10, 2)->setIsUnsigned()->setDefaultValue(0.00)->setColumnComment('');
        });
        $obj = Manager::getInstance()->get($name)->getObj();
        $obj->rawQuery($sql);
        return Manager::getInstance()->get($name)->recycleObj($obj);
    }


}
