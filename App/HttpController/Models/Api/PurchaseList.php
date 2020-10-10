<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class PurchaseList extends ModelBase
{
    protected $tableName = 'information_dance_purchase_list';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';
}
