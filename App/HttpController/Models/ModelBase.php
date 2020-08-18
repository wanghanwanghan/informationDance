<?php

namespace App\HttpController\Models;

use EasySwoole\ORM\AbstractModel;

class ModelBase extends AbstractModel
{
    function __construct(array $data = [])
    {
        parent::__construct($data);

        $this->connectionName = \Yaconf::get('env.mysqlDatabase');
    }
}
