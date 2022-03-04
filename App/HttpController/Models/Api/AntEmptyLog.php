<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class AntEmptyLog extends ModelBase
{
    protected $tableName = 'information_dance_ant_empty_log';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}