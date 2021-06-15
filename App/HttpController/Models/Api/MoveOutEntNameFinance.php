<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class MoveOutEntNameFinance extends ModelBase
{
    protected $tableName = 'information_dance_move_out_entname_finance';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
