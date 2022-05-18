<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class CompanyName extends ModelBase
{
    protected $tableName = 'company_name';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}