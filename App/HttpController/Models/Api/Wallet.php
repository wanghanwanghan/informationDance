<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class Wallet extends ModelBase
{
    protected $tableName = 'information_dance_wallet';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
