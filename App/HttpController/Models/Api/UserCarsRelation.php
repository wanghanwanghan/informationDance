<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UserCarsRelation extends ModelBase
{
    protected $tableName = 'user_cars_relation';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}