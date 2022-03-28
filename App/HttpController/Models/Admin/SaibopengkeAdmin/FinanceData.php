<?php

namespace App\HttpController\Models\Admin\SaibopengkeAdmin;

use App\HttpController\Models\ModelBase;

class FinanceData extends ModelBase
{
    protected $tableName = 'finance_data';
    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}