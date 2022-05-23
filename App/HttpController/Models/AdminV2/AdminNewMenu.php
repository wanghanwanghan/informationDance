<?php

namespace App\HttpController\Models\AdminNew;

use App\HttpController\Models\ModelBase;

class AdminNewMenu extends ModelBase
{
    protected $tableName = 'admin_new_menu';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
