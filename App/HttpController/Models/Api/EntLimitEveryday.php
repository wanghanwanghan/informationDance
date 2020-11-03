<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class EntLimitEveryday extends ModelBase
{
    protected $tableName = 'information_dance_ent_limit_everyday';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
