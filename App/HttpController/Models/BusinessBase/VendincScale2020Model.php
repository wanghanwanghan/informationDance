<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class VendincScale2020Model extends ModelBase
{
    protected $tableName = 'ar_lable';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }
}
