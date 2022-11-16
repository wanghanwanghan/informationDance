<?php

namespace App\HttpController\Models\RDS3\HdSaic;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class CodeRegion extends ModelBase
{
    protected $tableName = 'code_region';


    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic');
    }
}