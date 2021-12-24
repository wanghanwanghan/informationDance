<?php

namespace App\HttpController\Models\Api\FaDaDa;

use App\HttpController\Models\ModelBase;

class FaDaDaUserModel extends ModelBase
{
    protected $tableName = 'fa_da_da_user';

    protected $autoTimeStamp = true;
    protected $createTime = 'created_at';
    protected $updateTime = 'updated_at';

}
