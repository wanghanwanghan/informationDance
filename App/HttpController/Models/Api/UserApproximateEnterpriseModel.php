<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UserApproximateEnterpriseModel extends ModelBase
{
    protected $tableName = 'approximateenterprise_';
    protected $autoTimeStamp = false;

    function addSuffix(int $uid): UserApproximateEnterpriseModel
    {
        $suffix = $uid % 3;
        $this->tableName($this->tableName . $suffix);
        return $this;
    }

}
