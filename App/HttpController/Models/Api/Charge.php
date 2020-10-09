<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class Charge extends ModelBase
{
    protected $tableName = 'information_dance_charge';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
