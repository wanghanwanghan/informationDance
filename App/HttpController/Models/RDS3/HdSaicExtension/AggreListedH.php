<?php

namespace App\HttpController\Models\RDS3\HdSaicExtension;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\CreateConf;

class AggreListedH  extends ModelBase
{
    protected $tableName = 'aggre_listed_h';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabaseRDS_3_hd_saic_extension');
    }
}