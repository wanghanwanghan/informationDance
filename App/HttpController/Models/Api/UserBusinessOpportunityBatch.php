<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UserBusinessOpportunityBatch extends ModelBase
{
    protected $tableName = 'information_dance_user_business_opportunity_batch';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
    
}
