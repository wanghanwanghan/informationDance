<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class JinCaiTrace extends ModelBase
{
    protected $tableName = 'information_dance_jc_trace';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
