<?php

namespace App\HttpController\Models\Admin\SaibopengkeAdmin;

use App\HttpController\Models\ModelBase;

class FinanceChargeLog extends ModelBase
{
    protected $tableName = 'finance_charge_log';
    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}