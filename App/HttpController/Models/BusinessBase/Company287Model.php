<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class Company287Model extends ModelBase
{
    protected $tableName = 'company_287';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }
}
