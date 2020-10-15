<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class ReportInfo extends ModelBase
{
    protected $tableName = 'information_dance_report_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
