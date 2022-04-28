<?php

namespace App\HttpController\Models\RDS3;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class ArLable extends ModelBase
{
    protected $tableName = 'ar_lable';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }
    
}
