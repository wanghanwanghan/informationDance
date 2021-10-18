<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class FinancesSearch extends ModelBase
{
    protected $tableName = 'information_dance_finances_search_first';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
