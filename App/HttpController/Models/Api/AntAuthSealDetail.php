<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class AntAuthSealDetail extends ModelBase
{
    protected $tableName = 'information_dance_ant_auth_seal_detail';

    protected $autoTimeStamp = false;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}