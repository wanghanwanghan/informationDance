<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class NeoCrmPendingEnt extends ModelBase
{
    protected $tableName = 'neocrm_pending_ent';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
