<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class CompanyMortChange extends ModelBase
{
    protected $tableName = 'company_mort_change';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }
}