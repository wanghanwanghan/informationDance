<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;

class RequestUserInfoLog extends ModelBase
{
    protected $tableName = 'request_user_info_log';

    protected $autoTimeStamp = true;

    public static function addOne($username,$money){
        self::create()->data([
            'username' => $username,
            'create_time' => time(),
            'money' => $money,
        ])->save();
    }
}