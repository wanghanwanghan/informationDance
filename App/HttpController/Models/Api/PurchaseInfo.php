<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class PurchaseInfo extends ModelBase
{
    protected $tableName = 'information_dance_purchase_info';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
