<?php

use App\HttpController\Models\ModelBase;

class jieba_model extends ModelBase
{
    protected $tableName = 'tiao_ma_';

    protected $autoTimeStamp = false;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

    function addSuffix($suffix): jieba_model
    {
        $name = $this->tableName() . $suffix;
        $this->tableName($name);
        return $this;
    }
}
