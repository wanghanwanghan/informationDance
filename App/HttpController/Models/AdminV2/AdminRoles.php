<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\AdminRole\AdminRole;

class AdminRoles extends ModelBase
{
    protected $tableName = 'admin_roles';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

     

}
