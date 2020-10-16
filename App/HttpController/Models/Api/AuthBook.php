<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class AuthBook extends ModelBase
{
    protected $tableName = 'information_dance_auth_book';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
