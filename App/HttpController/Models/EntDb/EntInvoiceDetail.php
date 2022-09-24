<?php

namespace App\HttpController\Models\EntDb;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class EntInvoiceDetail extends ModelBase
{
    protected $tableName = 'invoice_detail';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');
    }

    function addSuffix(string $fpdm, string $fphm, string $type): EntInvoiceDetail
    {
        //01增值税专用发票 *** 本次蚂蚁用 type1
        //02货运运输业增值税专用发票
        //03机动车销售统一发票
        //04增值税普通发票 *** 本次蚂蚁用 type1
        //10增值税普通发票电子 *** 本次蚂蚁用 type1
        //11增值税普通发票卷式 *** 本次蚂蚁用 type1
        //14通行费电子票 *** 本次蚂蚁用 type2
        //15二手车销售统一发票

        switch ($type) {
            case 'test':
                $tablename = [$this->tableName, $type];
                break;
            default:
                $tablename = [$this->tableName, $this->suffixNum($fpdm . $fphm)];
        }

        $this->tableName(implode('_', $tablename));

        return $this;
    }

    //只含有26个字母或者数字的并且都是半角的字符串，转换成数字，用于hash分表
    function suffixNum(string $str): int
    {
        $j = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            is_numeric($str[$i]) ? $j += $str[$i] : $j += ord($str[$i]);
        }
        return $j % 10;
    }
}
