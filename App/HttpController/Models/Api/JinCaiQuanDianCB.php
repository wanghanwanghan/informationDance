<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class JinCaiQuanDianCB extends ModelBase
{
    protected $tableName = 'information_dance_jc_qd_callback';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
