<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UserBusinessOpportunity extends ModelBase
{
    protected $tableName = 'information_dance_user_business_opportunity';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
}
