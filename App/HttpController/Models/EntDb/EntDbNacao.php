<?php

namespace App\HttpController\Models\EntDb;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class EntDbNacao extends ModelBase
{
    protected $tableName = 'nacao';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');
    }
}
