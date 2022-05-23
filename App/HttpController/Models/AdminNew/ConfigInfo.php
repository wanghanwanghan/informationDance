<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\ModelBase;

class ConfigInfo extends ModelBase
{
    protected $tableName = 'config_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
