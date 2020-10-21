<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class LngLat extends ModelBase
{
    protected $tableName = 'information_dance_lng_lat';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
