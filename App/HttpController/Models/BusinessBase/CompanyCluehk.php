<?php

namespace App\HttpController\Models\BusinessBase;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class CompanyCluehk extends ModelBase
{
    protected $tableName = 'company_clue_';
    protected $autoTimeStamp = false;

    function __construct(array $data = [])
    {
        parent::__construct($data);
        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3');
    }

    function addSuffix($suffix): CompanyCluehk
    {
        $name = $this->tableName() . $suffix;
        $this->tableName($name);
        return $this;
    }

}
