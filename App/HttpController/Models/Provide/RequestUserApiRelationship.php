<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RequestUserApiRelationship extends ModelBase
{
    protected $tableName = 'information_dance_request_user_api_relationship';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
