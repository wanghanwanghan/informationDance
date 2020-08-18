<?php

namespace App\HttpController\Service\User;

use App\HttpController\Service\ServiceBase;
use EasySwoole\Component\Singleton;
use wanghanwanghan\someUtils\control;

class UserService extends ServiceBase
{
    use Singleton;

    //创建一个api请求token
    function createAccessToken($phone,$password): string
    {
        $str="{$phone}-{$password}-".time();

        return control::aesEncode($str,\Yaconf::get('env.salt'));
    }

    function decodeAccessToken($token): array
    {
        $str=control::aesDecode($token,\Yaconf::get('env.salt'));

        return explode('-',$str);
    }




}
