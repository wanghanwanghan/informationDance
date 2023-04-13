<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class TyyPendingEnt extends ModelBase
{
    protected $tableName = 'tyy_pending_ent';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
