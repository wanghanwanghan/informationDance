<?php

namespace App\HttpController\Models\AdminV2;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

// use App\HttpController\Models\AdminRole;

class UserFinanceDataBasicInfo extends ModelBase
{
    /*
    
        该用户具体客户名单的收费
    */
    protected $tableName = 'user_finance_data_basic_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

     
}
