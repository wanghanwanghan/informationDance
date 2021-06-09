<?php

namespace App\HttpController\Models\Api;

use App\HttpController\Models\ModelBase;

class MoveOutPhoneEntName extends ModelBase
{
    protected $tableName = 'information_dance_move_out_phone_entname';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
