<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class RequestRecode extends ModelBase
{
    protected $tableName = 'information_dance_request_recode_';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function addSuffix($suffix): RequestRecode
    {
        $name = $this->getTableName().$suffix;
        $this->tableName($name);
        return $this;
    }
}
