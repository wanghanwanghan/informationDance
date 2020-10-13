<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class OneSaid extends ModelBase
{
    protected $tableName = 'information_dance_one_said';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
