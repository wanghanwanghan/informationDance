<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class Statistics extends ModelBase
{
    protected $tableName = 'information_dance_statistics';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
