<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\ModelBase;

class AdminNewApi extends ModelBase
{
    protected $tableName = 'admin_new_api';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
