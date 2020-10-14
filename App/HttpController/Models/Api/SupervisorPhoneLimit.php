<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class SupervisorPhoneLimit extends ModelBase
{
    protected $tableName = 'information_dance_supervisor_uid_limit';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
