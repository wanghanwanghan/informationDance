<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Service\CreateConf;

class CompanyStockImpawn
{
    protected $tableName = 'company_stock_impawn';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }
}