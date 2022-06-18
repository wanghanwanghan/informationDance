<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class JincaiRwhLog extends ModelBase
{
    protected $tableName = 'information_dance_jincai_rwh_log';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}