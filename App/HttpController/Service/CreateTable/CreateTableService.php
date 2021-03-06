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

    //生成报告记录表
    function information_dance_report_info()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('生成报告记录表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colVarChar('filename', 50)->setDefaultValue('');
            $table->tinyint('type', 3)->setIsUnsigned()->setColumnComment('10是极简报告，30是简版报告，50是深度报告');
            $table->tinyint('status', 3)->setIsUnsigned()->setColumnComment('1是异常，2是完成，3是生成中');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
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

    //风险监控 用户阈值表
    function information_dance_supervisor_uid_limit()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('用户阈值表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colInt('sf', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('gs', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('gl', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('jy', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //授权书表
    function information_dance_auth_book()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('授权书表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colVarChar('entName', 50)->setDefaultValue('')->setColumnComment('公司名');
            $table->colVarChar('name', 50)->setDefaultValue('')->setColumnComment('授权书文件名');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setColumnComment('1审核中，2未通过，3已通过');
            $table->colVarChar('remark', 255)->setDefaultValue('')->setColumnComment('备注');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //授权书表
    function information_dance_lng_lat()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('经纬度表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('target', 50)->setDefaultValue('')->setColumnComment('主体，目前是手机号，以后也可以是别的');
            $table->colVarChar('lng', 15)->setDefaultValue('');
            $table->colVarChar('lat', 15)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //每天只能浏览100个企业
    function information_dance_ent_limit_everyday()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('每天只能浏览100个企业')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('token', 100)->setColumnComment('token');
            $table->colVarChar('entName', 100)->setColumnComment('企业名称');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('token_index', 'token');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //ocr识别消费队列
    function information_dance_ocr_queue()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('ocr识别消费队列')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('phone', 11)->setDefaultValue('');
            $table->colVarChar('reportNum', 30)->setDefaultValue('')->setColumnComment('报告编号');
            $table->colVarChar('catalogueNum', 10)->setDefaultValue('')->setColumnComment('目录编号');
            $table->colVarChar('catalogueName', 10)->setDefaultValue('')->setColumnComment('目录名称');
            $table->colTinyInt('status', 3)->setDefaultValue(0)->setColumnComment('状态');
            $table->colVarChar('filename', 255)->setDefaultValue('')->setColumnComment('文件名，逗号分割');
            $table->colText('content')->setColumnComment('识别出来的内容');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('phone_reportNum_index', ['reportNum', 'phone']);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //发票进项
    function information_dance_invoice_in()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('发票进项')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('invoiceCode', 50)->setDefaultValue('');
            $table->colVarChar('invoiceNumber', 50)->setDefaultValue('');
            $table->colVarChar('billingDate', 50)->setDefaultValue('');
            $table->colVarChar('totalAmount', 50)->setDefaultValue('');
            $table->colVarChar('totalTax', 50)->setDefaultValue('');
            $table->colVarChar('invoiceType', 50)->setDefaultValue('');
            $table->colVarChar('state', 50)->setDefaultValue('');
            $table->colVarChar('salesTaxNo', 50)->setDefaultValue('');
            $table->colVarChar('salesTaxName', 50)->setDefaultValue('');
            $table->colVarChar('purchaserTaxNo', 50)->setDefaultValue('');
            $table->colVarChar('purchaserName', 50)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('invoiceCode_invoiceNumber_index', ['invoiceCode', 'invoiceNumber']);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //对外接口 计费
    function information_dance_request_recode()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('对外接口计费')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colBigInt('id', 20)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('userId', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户主键');
            $table->colInt('ProvideApiId', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('接口主键');
            $table->colVarChar('requestId', 32)->setDefaultValue('')->setColumnComment('请求唯一主键');
            $table->colVarChar('requestUrl', 256)->setDefaultValue('')->setColumnComment('请求url');
            $table->colText('requestData')->setColumnComment('请求参数');
            $table->colInt('responseCode', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('返回值');
            $table->colText('responseData')->setColumnComment('返回结果');
            $table->colDecimal('spendTime', 8, 4)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('请求消耗时间');
            $table->colDecimal('spendMoney', 8, 4)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('消耗金额');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //对外接口 公司信息
    function information_dance_request_user_info()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('公司信息')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('username', 32)->setDefaultValue('')->setColumnComment('公司/用户名称');
            $table->colVarChar('appId', 64)->setDefaultValue('');
            $table->colVarChar('appSecret', 64)->setDefaultValue('');
            $table->colVarChar('allowIp', 128)->setDefaultValue('');
            $table->colDecimal('money', 16, 4)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('余额');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setDefaultValue(1)->setColumnComment('状态，1可用，2不可用');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('appId_index', 'appId');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //对外接口 接口信息
    function information_dance_request_api_info()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('接口信息')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('path', 128)->setDefaultValue('')->setColumnComment('接口地址');
            $table->colVarChar('name', 32)->setDefaultValue('')->setColumnComment('接口名称');
            $table->colVarChar('desc', 128)->setDefaultValue('')->setColumnComment('接口描述');
            $table->colVarChar('source', 32)->setDefaultValue('')->setColumnComment('接口来源');
            $table->colDecimal('price', 8, 4)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('成本价格');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setDefaultValue(1)->setColumnComment('状态，1可用，2不可用');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('path_index', 'path');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //对外接口 用户-接口关系表
    function information_dance_request_user_api_relationship()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('用户-接口关系表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('userId', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户主键');
            $table->colInt('apiId', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('接口主键');
            $table->colDecimal('price', 8, 4)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('用户调用价格');
            $table->colTinyInt('status', 3)->setIsUnsigned()->setDefaultValue(1)->setColumnComment('状态，1可用，2不可用');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('userId_index', 'userId');
            $table->indexNormal('apiId_index', 'apiId');
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }

    //财务信息表
    function finance()
    {
        $name = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');

        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('财务信息表')
                ->setTableEngine(Engine::INNODB)
                ->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('cid', 11)->setIsUnsigned()->setDefaultValue(0)->setColumnComment('公司id');
            $table->colInt('ANCHEYEAR', 11)->setIsUnsigned()->setDefaultValue(null)->setColumnComment('年份');
            $table->colDecimal('VENDINC', 20, 2)->setDefaultValue(null)->setColumnComment('营业总收入');
            $table->colDecimal('ASSGRO', 20, 2)->setDefaultValue(null)->setColumnComment('资产总额');
            $table->colDecimal('MAIBUSINC', 20, 2)->setDefaultValue(null)->setColumnComment('主营业务收入');
            $table->colDecimal('TOTEQU', 20, 2)->setDefaultValue(null)->setColumnComment('所有者权益');
            $table->colDecimal('RATGRO', 20, 2)->setDefaultValue(null)->setColumnComment('纳税总额');
            $table->colDecimal('PROGRO', 20, 2)->setDefaultValue(null)->setColumnComment('利润总额');
            $table->colDecimal('NETINC', 20, 2)->setDefaultValue(null)->setColumnComment('净利润');
            $table->colDecimal('LIAGRO', 20, 2)->setDefaultValue(null)->setColumnComment('负债总额');
            $table->colDecimal('So1', 20, 2)->setDefaultValue(null)->setColumnComment('城镇职工养老保险人数');
            $table->colDecimal('So2', 20, 2)->setDefaultValue(null)->setColumnComment('失业保险人数');
            $table->colDecimal('So3', 20, 2)->setDefaultValue(null)->setColumnComment('职工医疗保险人数');
            $table->colDecimal('So4', 20, 2)->setDefaultValue(null)->setColumnComment('工伤保险人数');
            $table->colDecimal('So5', 20, 2)->setDefaultValue(null)->setColumnComment('生育保险人数');
            $table->colDecimal('totalWagesSo1', 20, 2)->setDefaultValue(null)->setColumnComment('城镇职工养老保险缴费基数');
            $table->colDecimal('totalWagesSo2', 20, 2)->setDefaultValue(null)->setColumnComment('失业保险缴费基数');
            $table->colDecimal('totalWagesSo3', 20, 2)->setDefaultValue(null)->setColumnComment('职工医疗保险缴费基数');
            $table->colDecimal('totalWagesSo4', 20, 2)->setDefaultValue(null)->setColumnComment('工伤保险缴费基数 保留字段');
            $table->colDecimal('totalWagesSo5', 20, 2)->setDefaultValue(null)->setColumnComment('生育保险缴费基数');
            $table->colDecimal('totalPaymentSo1', 20, 2)->setDefaultValue(null)->setColumnComment('城镇职工养老保险实缴基数');
            $table->colDecimal('totalPaymentSo2', 20, 2)->setDefaultValue(null)->setColumnComment('失业保险实缴基数');
            $table->colDecimal('totalPaymentSo3', 20, 2)->setDefaultValue(null)->setColumnComment('职工医疗保险实缴基数');
            $table->colDecimal('totalPaymentSo4', 20, 2)->setDefaultValue(null)->setColumnComment('工伤保险实缴基数');
            $table->colDecimal('totalPaymentSo5', 20, 2)->setDefaultValue(null)->setColumnComment('生育保险实缴基数');
            $table->colDecimal('unPaidSocialInsSo1', 20, 2)->setDefaultValue(null)->setColumnComment('城镇职工养老保险累计欠缴');
            $table->colDecimal('unPaidSocialInsSo2', 20, 2)->setDefaultValue(null)->setColumnComment('失业保险累计欠缴');
            $table->colDecimal('unPaidSocialInsSo3', 20, 2)->setDefaultValue(null)->setColumnComment('职工医疗保险累计欠缴');
            $table->colDecimal('unPaidSocialInsSo4', 20, 2)->setDefaultValue(null)->setColumnComment('工伤保险累计欠缴');
            $table->colDecimal('unPaidSocialInsSo5', 20, 2)->setDefaultValue(null)->setColumnComment('生育保险累计欠缴');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->indexNormal('cid_year_index', ['cid', 'ANCHEYEAR']);
        });

        $obj = Manager::getInstance()->get($name)->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get($name)->recycleObj($obj);

        return 'ok';
    }

    //公司表
    function ent()
    {
        $name = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');

        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('公司表')
                ->setTableEngine(Engine::INNODB)
                ->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('name', 128)->setDefaultValue('')->setColumnComment('公司名称');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get($name)->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get($name)->recycleObj($obj);

        return 'ok';
    }

    function basic()
    {
        $name = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');

        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('企业基本信息表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('ENTNAME', 64)->setDefaultValue('')->setColumnComment('公司名称');
            $table->colVarChar('OLDNAME', 128)->setDefaultValue('')->setColumnComment('旧公司名称');
            $table->colVarChar('SHXYDM', 32)->setDefaultValue('')->setColumnComment('统一代码');
            $table->colVarChar('FRDB', 16)->setDefaultValue('')->setColumnComment('法人');
            $table->colVarChar('ESDATE', 16)->setDefaultValue('')->setColumnComment('成立时间');
            $table->colVarChar('ENTSTATUS', 16)->setDefaultValue('')->setColumnComment('状态');
            $table->colDecimal('REGCAP', 14, 2)->setIsUnsigned()->setDefaultValue(0.00)->setColumnComment('注册资本');
            $table->colVarChar('REGCAPCUR', 16)->setDefaultValue('')->setColumnComment('币种');
            $table->colVarChar('DOM', 128)->setDefaultValue('')->setColumnComment('注册地址');
            $table->colVarChar('ENTTYPE', 64)->setDefaultValue('')->setColumnComment('类型');
            $table->colText('OPSCOPE')->setColumnComment('经营范围');
            $table->colVarChar('REGORG', 64)->setDefaultValue('')->setColumnComment('所属部门');
            $table->colVarChar('OPFROM', 16)->setDefaultValue('')->setColumnComment('OPFROM');
            $table->colVarChar('OPTO', 16)->setDefaultValue('')->setColumnComment('OPTO');
            $table->colVarChar('APPRDATE', 16)->setDefaultValue('')->setColumnComment('APPRDATE');
            $table->colVarChar('ENDDATE', 16)->setDefaultValue('')->setColumnComment('ENDDATE');
            $table->colVarChar('REVDATE', 16)->setDefaultValue('')->setColumnComment('REVDATE');
            $table->colVarChar('CANDATE', 16)->setDefaultValue('')->setColumnComment('CANDATE');
            $table->colVarChar('JWD', 32)->setDefaultValue('')->setColumnComment('经纬度');
            $table->colVarChar('INDUSTRY', 32)->setDefaultValue('')->setColumnComment('行业');
            $table->colVarChar('INDUSTRY_CODE', 4)->setDefaultValue('')->setColumnComment('INDUSTRY_CODE');
            $table->colVarChar('PROVINCE', 16)->setDefaultValue('')->setColumnComment('省份');
            $table->colVarChar('ORGID', 16)->setDefaultValue('')->setColumnComment('ORGID');
            $table->colVarChar('ENGNAME', 64)->setDefaultValue('')->setColumnComment('公司英文名称');
            $table->colVarChar('WEBSITE', 64)->setDefaultValue('')->setColumnComment('网站');
            $table->colVarChar('CHANGE_TYPE', 4)->setDefaultValue('')->setColumnComment('CHANGE_TYPE');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get($name)->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get($name)->recycleObj($obj);

        return 'ok';
    }

    function inv()
    {
        $name = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');

        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('企业股东表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colVarChar('ENTNAME', 64)->setDefaultValue('')->setColumnComment('公司名称');
            $table->colVarChar('INV', 32)->setDefaultValue('')->setColumnComment('股东名称');
            $table->colVarChar('SHXYDM', 32)->setDefaultValue('')->setColumnComment('统一代码');
            $table->colVarChar('INVTYPE', 16)->setDefaultValue('')->setColumnComment('股东类型');
            $table->colDecimal('SUBCONAM', 18, 4)->setIsUnsigned()->setDefaultValue(0.0000)->setColumnComment('出资金额');
            $table->colVarChar('CONCUR', 8)->setDefaultValue('')->setColumnComment('币种');
            $table->colDecimal('CONRATIO', 10, 4)->setIsUnsigned()->setDefaultValue(0.0000)->setColumnComment('出资比例');
            $table->colVarChar('CONDATE', 16)->setDefaultValue('')->setColumnComment('时间');
            $table->colVarChar('CHANGE_TYPE', 4)->setDefaultValue('')->setColumnComment('CHANGE_TYPE');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get($name)->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get($name)->recycleObj($obj);

        return 'ok';
    }

    function information_dance_move_out_phone_entname()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('迁移用户企业关联表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
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

    function information_dance_move_out_entname_rel()
    {
        $sql = DDLBuilder::table(__FUNCTION__, function (Table $table) {
            $table->setTableComment('企业迁移表')->setTableEngine(Engine::INNODB)->setTableCharset(Character::UTF8MB4_GENERAL_CI);
            $table->colInt('id', 11)->setIsAutoIncrement()->setIsUnsigned()->setIsPrimaryKey()->setColumnComment('主键');
            $table->colInt('pid', 11)->setIsUnsigned()->setColumnComment('属于监控表中哪个公司');
            $table->colInt('bid', 11)->setIsUnsigned()->setColumnComment('basic表中的id');
            $table->colVarChar('entName', 50)->setDefaultValue('');
            $table->colInt('created_at', 11)->setIsUnsigned()->setDefaultValue(0);
            $table->colInt('updated_at', 11)->setIsUnsigned()->setDefaultValue(0);
        });

        $obj = Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->getObj();

        $obj->rawQuery($sql);

        Manager::getInstance()->get(CreateConf::getInstance()->getConf('env.mysqlDatabase'))->recycleObj($obj);

        return 'ok';
    }


}
