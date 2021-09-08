<?php

namespace App\HttpController\Models\EntDb;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class EntDbTzList extends ModelBase
{
    protected $tableName = 'tz_list';
    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseEntDb');
    }
}
