<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;

class AdminNewApiField extends ModelBase
{
    protected $tableName = 'admin_new_api_field';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
