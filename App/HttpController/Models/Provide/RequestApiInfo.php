<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RequestApiInfo extends ModelBase
{
    protected $tableName = 'information_dance_request_api_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
