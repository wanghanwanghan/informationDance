<?php

namespace App\HttpController\Models\Provide;

use App\HttpController\Models\ModelBase;
use App\HttpController\Service\Common\CommonService;

class RequestUserInfoLog extends ModelBase
{
    protected $tableName = 'request_user_info_log';

    protected $autoTimeStamp = true;

    public function addOne($username,$money){
        $res = self::create()->data([
            'username' => $username,
            'create_time' => time(),
            'money' => $money,
        ])->save();
    }
}