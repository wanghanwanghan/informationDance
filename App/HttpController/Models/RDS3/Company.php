<?php

namespace App\HttpController\Models\RDS3;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class Company extends ModelBase
{
    protected $tableName = 'company';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }
    
}
