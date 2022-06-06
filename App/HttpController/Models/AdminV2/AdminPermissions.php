<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\AdminRole\AdminRole;

class AdminPermissions extends ModelBase
{
    protected $tableName = 'admin_permissions';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

     

}
