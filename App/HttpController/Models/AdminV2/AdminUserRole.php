<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;

class AdminUserRole extends ModelBase
{
    protected $tableName = 'admin_user_role';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
