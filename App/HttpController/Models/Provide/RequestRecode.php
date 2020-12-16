<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RequestRecode extends ModelBase
{
    protected $tableName = 'information_dance_request_recode_';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function addSuffix($suffix): RequestRecode
    {
        $name = $this->tableName().$suffix;
        $this->tableName($name);
        return $this;
    }
}
