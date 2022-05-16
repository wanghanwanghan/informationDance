<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class DianZiQianAuth extends ModelBase
{
    protected $tableName = 'dian_zi_qian_auth';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}