<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;

class AdminNewUser extends ModelBase
{
    protected $tableName = 'admin_new_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}