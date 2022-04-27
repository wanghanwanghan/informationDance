<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class UserSearchHistory extends ModelBase
{
    protected $tableName = 'information_dance_user_search_history';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
