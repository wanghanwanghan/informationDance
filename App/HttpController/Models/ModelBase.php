<?php

namespace App\HttpController\Models;

use App\HttpController\Service\CreateConf;
use EasySwoole\ORM\AbstractModel;

class ModelBase extends AbstractModel
{
    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = CreateConf::getInstance()->getConf('env.mysqlDatabase');
    }
}
