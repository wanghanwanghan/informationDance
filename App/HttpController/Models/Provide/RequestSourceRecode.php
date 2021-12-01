<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RequestSourceRecode extends ModelBase
{
    protected $tableName = 'information_dance_request_source_recode_';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function addSuffix($suffix): RequestSourceRecode
    {
        $name = $this->tableName() . $suffix;
        $this->tableName($name);
        return $this;
    }
}
