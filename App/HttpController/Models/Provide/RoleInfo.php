<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RoleInfo extends ModelBase
{
    protected $tableName = 'information_dance_role_info';

    protected $autoTimeStamp = true;

    public function addOne($username,$money){
        $res = self::create()->data([
            'username' => $username,
            'create_time' => time(),
            'money' => $money,
        ])->save();
    }
}