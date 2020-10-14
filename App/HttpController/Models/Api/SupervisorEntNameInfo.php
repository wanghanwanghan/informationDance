<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class SupervisorEntNameInfo extends ModelBase
{
    protected $tableName = 'information_dance_supervisor_entname_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
