<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class BatchSeachLog extends ModelBase
{
    protected $tableName = 'information_dance_batch_seach_log';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}