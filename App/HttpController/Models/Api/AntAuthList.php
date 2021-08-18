<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class AntAuthList extends ModelBase
{
    protected $tableName = 'information_dance_ant_auth_list';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
